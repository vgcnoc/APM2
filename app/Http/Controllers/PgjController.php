<?php

namespace App\Http\Controllers;

use App\Models\PgjTicket;
use App\Models\PgjMaterial;
use App\Models\Area;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Customer;
use App\Models\User;
use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\AuditLog;
use App\Services\DataScopeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class PgjController extends Controller
{
    public function __construct()
    {
        try {
            Role::firstOrCreate(['name' => 'kepala teknisi']);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Halaman Utama Penanganan Gangguan Jalur (PGJ) - Penerimaan & Penanganan Admin
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_pgj')) {
            if ($user->can('menu_pgj_laporan')) {
                return redirect()->route('pgj.laporan');
            }
            if ($user->can('menu_pgj_jadwal')) {
                return redirect()->route('pgj.jadwal');
            }
            abort(403, 'Akses ke modul PGJ tidak diizinkan.');
        }

        $query = PgjTicket::with([
            'area',
            'odc',
            'odp.odc',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'resolvedByUser'
        ]);

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyPgjScope($query);

        // Filter pencarian
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('reported_issue', 'like', "%{$search}%")
                  ->orWhere('core_details', 'like', "%{$search}%")
                  ->orWhereHas('odc', function($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('odp', function($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter prioritas
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter segmen
        if ($request->filled('segment_type')) {
            $query->where('segment_type', $request->segment_type);
        }

        // Filter area
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Hitung statistik kasus jalur
        $counts = [
            'all' => PgjTicket::count(),
            'admin_handling' => PgjTicket::where('status', 'admin_handling')->count(),
            'escalated_to_lead' => PgjTicket::where('status', 'escalated_to_lead')->count(),
            'scheduled' => PgjTicket::where('status', 'scheduled')->count(),
            'completed' => PgjTicket::where('status', 'completed')->count(),
        ];

        $areas = Area::orderBy('name')->get();
        $odcs = Odc::withCount('odps')->orderBy('name')->get();
        $odps = Odp::with('odc')->orderBy('name')->get();

        $technicians = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['teknisi', 'kepala teknisi']);
        })->orWhere('role', 'teknisi')->orderBy('name')->get();

        $leadTechnicians = User::role(['kepala teknisi', 'admin', 'noc', 'manager'])->select('id', 'name')->get();
        if ($leadTechnicians->isEmpty()) {
            $leadTechnicians = User::select('id', 'name')->get();
        }

        return Inertia::render('Pgj/Index', [
            'tickets' => $tickets,
            'areas' => $areas,
            'odcs' => $odcs,
            'odps' => $odps,
            'technicians' => $technicians,
            'leadTechnicians' => $leadTechnicians,
            'counts' => $counts,
            'filters' => $request->only(['search', 'status', 'priority', 'segment_type', 'area_id']),
        ]);
    }

    /**
     * Halaman Penjadwalan & Penyiapan Material Jalur (Kepala Teknisi)
     */
    public function jadwal(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_pgj_jadwal')) {
            if ($user->can('menu_pgj_laporan')) {
                return redirect()->route('pgj.laporan');
            }
            if ($user->can('menu_pgj')) {
                return redirect()->route('pgj.index');
            }
            abort(403, 'Akses ke jadwal PGJ tidak diizinkan.');
        }

        $query = PgjTicket::with([
            'area',
            'odc',
            'odp.odc',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
        ])->whereIn('status', ['escalated_to_lead', 'scheduled']);

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyPgjScope($query);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('reported_issue', 'like', "%{$search}%")
                  ->orWhereHas('odc', function($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('odp', function($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        $tickets = $query->orderBy('priority', 'desc')
                         ->orderBy('created_at', 'desc')
                         ->paginate(15)
                         ->withQueryString();

        $areas = Area::orderBy('name')->get();

        $technicians = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['teknisi', 'kepala teknisi']);
        })->orWhere('role', 'teknisi')->orderBy('name')->get();

        $materials = Material::with('stocks')
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get();
        $materialTransactions = MaterialTransaction::where('type', 'in')
            ->with(['items.material.stocks', 'areaModel'])
            ->latest('id')
            ->limit(200)
            ->get();

        return Inertia::render('Pgj/Jadwal', [
            'tickets' => $tickets,
            'areas' => $areas,
            'technicians' => $technicians,
            'materials' => $materials,
            'materialTransactions' => $materialTransactions,
            'filters' => $request->only(['search', 'area_id']),
        ]);
    }

    /**
     * Halaman Pelaporan Hasil Penanganan Jalur (Teknisi)
     */
    public function laporan(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_pgj_laporan')) {
            if ($user->can('menu_pgj')) {
                return redirect()->route('pgj.index');
            }
            if ($user->can('menu_pgj_jadwal')) {
                return redirect()->route('pgj.jadwal');
            }
            abort(403, 'Akses ke laporan PGJ tidak diizinkan.');
        }

        $query = PgjTicket::with([
            'area',
            'odc',
            'odp.odc',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'resolvedByUser'
        ])->whereIn('status', ['scheduled', 'completed']);

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyPgjScope($query);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%")
                  ->orWhere('action_taken', 'like', "%{$search}%")
                  ->orWhere('core_details', 'like', "%{$search}%")
                  ->orWhereHas('odc', function($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('odp', function($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $areas = Area::orderBy('name')->get();

        return Inertia::render('Pgj/Laporan', [
            'tickets' => $tickets,
            'areas' => $areas,
            'filters' => $request->only(['search', 'area_id']),
        ]);
    }

    /**
     * Hitung Estimasi Dampak Pelanggan (Helper)
     */
    private function calculateImpact(string $segmentType, ?int $odcId, ?int $odpId, ?int $areaId): int
    {
        if ($segmentType === 'odp' && $odpId) {
            // Hitung pelanggan yang ont-nya di odp ini
            return Customer::whereHas('ont', function($q) use ($odpId) {
                $q->where('odp_id', $odpId);
            })->count();
        }

        if (($segmentType === 'odc' || $segmentType === 'distribution') && $odcId) {
            // Hitung pelanggan di seluruh odp di bawah odc ini
            return Customer::whereHas('ont.odp', function($q) use ($odcId) {
                $q->where('odc_id', $odcId);
            })->count();
        }

        if ($segmentType === 'backbone') {
            if ($odcId) {
                return Customer::whereHas('ont.odp', function($q) use ($odcId) {
                    $q->where('odc_id', $odcId);
                })->count();
            } elseif ($areaId) {
                return Customer::where('area_id', $areaId)->count();
            }
        }

        return 0;
    }

    /**
     * Simpan Temuan / Komplain Gangguan Jalur Baru (Admin)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can(['pgj_btn_create', 'pgj_manage']) && !$user->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat laporan PGJ.');
        }

        $validated = $request->validate([
            'segment_type' => 'required|in:backbone,odc,odp,distribution,other',
            'area_id' => 'nullable|exists:areas,id',
            'odc_id' => 'nullable|exists:odcs,id',
            'odp_id' => 'nullable|exists:odps,id',
            'location_name' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'otdr_distance' => 'nullable|string|max:100',
            'source_report' => 'required|string|max:100',
            'category' => 'required|string|max:150',
            'priority' => 'required|in:low,medium,high,critical',
            'reported_issue' => 'required|string',
            'admin_notes' => 'nullable|string',
        ]);

        // Otomatis hitung perkiraan pelanggan terdampak
        $impacted = $this->calculateImpact(
            $validated['segment_type'],
            $validated['odc_id'] ?? null,
            $validated['odp_id'] ?? null,
            $validated['area_id'] ?? null
        );

        // Jika lokasi/koordinat kosong, ambil dari ODC atau ODP terpilih
        if (empty($validated['location_name'])) {
            if (!empty($validated['odp_id'])) {
                $odp = Odp::find($validated['odp_id']);
                $validated['location_name'] = $odp?->address ?: 'Lokasi ' . $odp?->name;
                $validated['latitude'] = $validated['latitude'] ?? $odp?->latitude;
                $validated['longitude'] = $validated['longitude'] ?? $odp?->longitude;
            } elseif (!empty($validated['odc_id'])) {
                $odc = Odc::find($validated['odc_id']);
                $validated['location_name'] = $odc?->location ?: 'Lokasi ' . $odc?->name;
                $validated['latitude'] = $validated['latitude'] ?? $odc?->latitude;
                $validated['longitude'] = $validated['longitude'] ?? $odc?->longitude;
            }
        }

        $ticket = PgjTicket::create([
            'segment_type' => $validated['segment_type'],
            'area_id' => $validated['area_id'] ?? null,
            'odc_id' => $validated['odc_id'] ?? null,
            'odp_id' => $validated['odp_id'] ?? null,
            'affected_customers_count' => $impacted,
            'location_name' => $validated['location_name'] ?? 'Titik Jalur Jaringan',
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'otdr_distance' => $validated['otdr_distance'] ?? null,
            'source_report' => $validated['source_report'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'status' => 'admin_handling',
            'reported_issue' => $validated['reported_issue'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        AuditLog::createLog(
            'Buat Tiket PGJ Baru',
            $ticket,
            null,
            'admin_handling',
            "Menerima temuan gangguan jalur {$ticket->ticket_number} [{$ticket->segment_type}]: {$ticket->category} (Dampak: {$impacted} pelanggan)"
        );

        return redirect()->back()->with('success', "Tiket Penanganan Gangguan Jalur #{$ticket->ticket_number} berhasil dibuat. Estimasi dampak: {$impacted} pelanggan.");
    }

    /**
     * Selesaikan Langsung oleh Admin (Jarak Jauh / False Alarm / Pulih Sendiri)
     */
    public function resolveByAdmin(Request $request, PgjTicket $pgj)
    {
        $user = $request->user();
        if ($user && !$user->can(['pgj_btn_resolve', 'pgj_manage']) && !$user->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menyelesaikan PGJ.');
        }

        $validated = $request->validate([
            'admin_notes' => 'required|string',
        ]);

        $oldStatus = $pgj->status;
        $pgj->update([
            'status' => 'completed',
            'admin_notes' => $validated['admin_notes'],
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        AuditLog::createLog(
            'PGJ Selesai oleh Admin',
            $pgj,
            $oldStatus,
            'completed',
            'Admin menyelesaikan gangguan jalur dari jarak jauh: ' . $validated['admin_notes']
        );

        return redirect()->back()->with('success', "Tiket PGJ #{$pgj->ticket_number} berhasil diselesaikan oleh Admin.");
    }

    /**
     * Eskalasi ke Kepala Teknisi untuk Penanganan Lapangan
     */
    public function escalateToLead(Request $request, PgjTicket $pgj)
    {
        $user = $request->user();
        if ($user && !$user->can(['pgj_btn_escalate', 'pgj_manage']) && !$user->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengeskalasi PGJ.');
        }

        $validated = $request->validate([
            'escalation_reason' => 'required|string',
            'lead_technician_id' => 'nullable|exists:users,id',
            'admin_notes' => 'nullable|string',
        ]);

        $oldStatus = $pgj->status;
        $pgj->update([
            'status' => 'escalated_to_lead',
            'escalation_reason' => $validated['escalation_reason'],
            'admin_notes' => $validated['admin_notes'] ?: $pgj->admin_notes,
            'lead_technician_id' => $validated['lead_technician_id'] ?? $pgj->lead_technician_id,
        ]);

        AuditLog::createLog(
            'Eskalasi PGJ ke Ka. Teknisi',
            $pgj,
            $oldStatus,
            'escalated_to_lead',
            'Admin mengeskalasi perbaikan jalur ke Kepala Teknisi: ' . $validated['escalation_reason']
        );

        return redirect()->back()->with('success', "Tiket PGJ #{$pgj->ticket_number} telah dieskalasi ke Kepala Teknisi untuk dijadwalkan.");
    }

    /**
     * Penjadwalan & Penyiapan Material oleh Kepala Teknisi
     */
    public function schedule(Request $request, PgjTicket $pgj)
    {
        $user = $request->user();
        if ($user && !$user->can(['pgj_btn_assign', 'pgj_manage']) && !$user->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menjadwalkan teknisi PGJ.');
        }

        // Normalisasi parameter teknisi
        if ($request->has('technicians') && !$request->has('technician_ids')) {
            $request->merge(['technician_ids' => $request->technicians]);
        }
        if ($request->has('technician_ids') && !$request->has('technicians')) {
            $request->merge(['technicians' => $request->technician_ids]);
        }

        // Normalisasi parameter materials
        if ($request->has('materials') && is_array($request->materials)) {
            $mats = $request->materials;
            foreach ($mats as $k => $m) {
                if (isset($m['quantity_prepared']) && !isset($m['quantity'])) {
                    $mats[$k]['quantity'] = $m['quantity_prepared'];
                } elseif (isset($m['quantity']) && !isset($m['quantity_prepared'])) {
                    $mats[$k]['quantity_prepared'] = $m['quantity'];
                }
            }
            $request->merge(['materials' => $mats]);
        }

        $validated = $request->validate([
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|string|max:100',
            'lead_notes' => 'nullable|string',
            'technician_ids' => 'required_without:technicians|array|min:1',
            'technician_ids.*' => 'exists:users,id',
            'technicians' => 'sometimes|array',
            'technicians.*' => 'exists:users,id',
            'materials' => 'nullable|array',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.material_transaction_item_id' => 'nullable',
            'materials.*.trx_number' => 'nullable|string|max:100',
            'materials.*.quantity' => 'nullable|numeric|min:0.01',
            'materials.*.quantity_prepared' => 'nullable|numeric|min:0.01',
            'materials.*.unit' => 'nullable|string',
            'materials.*.notes' => 'nullable|string',
        ]);

        $oldStatus = $pgj->status;

        $pgj->update([
            'status' => 'scheduled',
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time' => $validated['scheduled_time'] ?? null,
            'lead_notes' => $validated['lead_notes'] ?? null,
            'lead_technician_id' => auth()->id(),
        ]);

        // Simpan penugasan tim teknisi
        $techIds = $request->technician_ids ?? $request->technicians ?? [];
        $pgj->technicians()->sync($techIds);

        // Kelola alokasi material gudang jalur
        if (!empty($validated['materials'])) {
            // Kembalikan alokasi material sebelumnya jika ada revisi jadwal
            $previousMaterials = $pgj->materials()->get();
            foreach ($previousMaterials as $prevMat) {
                $prevQty = (float) $prevMat->quantity_prepared;
                if ($prevQty > 0) {
                    if ($pgj->area_id) {
                        $matStock = \App\Models\MaterialStock::where('material_id', $prevMat->material_id)
                            ->where('area_id', $pgj->area_id)
                            ->first();
                        if ($matStock) {
                            $matStock->increment('stock', $prevQty);
                        }
                    }
                    Material::where('id', $prevMat->material_id)->increment('stock', $prevQty);
                }
            }

            $pgj->materials()->delete();

            $techNames = User::whereIn('id', $techIds)->pluck('name')->join(', ');

            $sourceOrders = array_filter(array_unique(array_column($validated['materials'], 'trx_number')));
            $orderRefStr = !empty($sourceOrders) ? ' (Ref Order/SJ: ' . implode(', ', $sourceOrders) . ')' : '';

            $transaction = MaterialTransaction::create([
                'transaction_number' => 'OUT-PGJ-' . date('YmdHis') . rand(10, 99),
                'user_id' => auth()->id(),
                'type' => 'out',
                'status' => 'approved',
                'technician_name' => $techNames ?: auth()->user()->name,
                'purpose' => "Material Jalur PGJ #{$pgj->ticket_number} ({$pgj->segment_type}: {$pgj->category}){$orderRefStr}",
                'date' => now(),
                'area_id' => $pgj->area_id,
            ]);

            foreach ($validated['materials'] as $matItem) {
                if (empty($matItem['material_id'])) continue;
                $material = Material::find($matItem['material_id']);
                $qty = (float) ($matItem['quantity'] ?? $matItem['quantity_prepared'] ?? 0);
                if ($qty <= 0) continue;

                if ($material) {
                    $stockBefore = (float) $material->stock;
                    $stockAfter = max(0, $stockBefore - $qty);

                    // Kurangkan stok area jika ada
                    if ($pgj->area_id) {
                        $matStock = \App\Models\MaterialStock::where('material_id', $material->id)
                            ->where('area_id', $pgj->area_id)
                            ->first();
                        if ($matStock) {
                            $matStock->decrement('stock', $qty);
                        }
                    }

                    $material->decrement('stock', $qty);

                    $unit = $matItem['unit'] ?? $material->unit;
                    $ppu = $material->price ?? ($material->selling_price ?: ($material->price_per_unit ?? 0));

                    MaterialTransactionItem::create([
                        'material_transaction_id' => $transaction->id,
                        'material_id' => $material->id,
                        'quantity' => $qty,
                        'unit' => $unit,
                        'price_per_unit' => $ppu,
                        'total_price' => $ppu * $qty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                    ]);

                    PgjMaterial::create([
                        'pgj_ticket_id' => $pgj->id,
                        'material_id' => $material->id,
                        'material_transaction_item_id' => $matItem['material_transaction_item_id'] ?? null,
                        'trx_number' => $matItem['trx_number'] ?? null,
                        'quantity_prepared' => $qty,
                        'quantity_used' => 0,
                        'quantity_returned' => 0,
                        'unit' => $unit,
                        'notes' => $matItem['notes'] ?? null,
                    ]);
                }
            }
        }

        AuditLog::createLog(
            'Penjadwalan PGJ oleh Ka. Teknisi',
            $pgj,
            $oldStatus,
            'scheduled',
            "Ka. Teknisi menjadwalkan perbaikan jalur pada {$validated['scheduled_date']} dengan " . count($validated['technician_ids']) . " teknisi."
        );

        return redirect()->back()->with('success', "Tiket PGJ #{$pgj->ticket_number} berhasil dijadwalkan dan material telah disiapkan dari gudang.");
    }

    /**
     * Pelaporan Hasil Restorasi Jalur oleh Teknisi
     */
    public function submitReport(Request $request, PgjTicket $pgj)
    {
        $user = $request->user();
        if ($user && !$user->can(['pgj_btn_report', 'pgj_manage']) && !$user->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melaporkan hasil restorasi PGJ.');
        }

        // Jika scope 'own' (teknisi biasa), verifikasi bahwa user memang ditugaskan pada PGJ ini
        if ($user && \App\Services\DataScopeService::getScopeLevel($user, 'pgj') === 'own') {
            $isAssigned = $pgj->technicians()->where('users.id', $user->id)->exists()
                || $pgj->created_by === $user->id
                || $pgj->lead_technician_id === $user->id;
            if (!$isAssigned) {
                abort(403, 'Anda hanya dapat melaporkan tugas PGJ yang ditugaskan kepada Anda.');
            }
        }
        $validated = $request->validate([
            'action_taken' => 'required|string',
            'core_details' => 'nullable|string|max:255',
            'optical_loss_before' => 'nullable|string|max:100',
            'optical_loss_after' => 'nullable|string|max:100',
            'start_time' => 'nullable|string|max:50',
            'end_time' => 'nullable|string|max:50',
            'resolution_notes' => 'nullable|string',
            'photo_before' => 'nullable|image|max:5120',
            'photo_after' => 'nullable|image|max:5120',
            'materials' => 'nullable|array',
            'materials.*.pgj_material_id' => 'required|exists:pgj_materials,id',
            'materials.*.quantity_used' => 'required|numeric|min:0',
        ]);

        $photoBeforePath = $pgj->photo_before;
        if ($request->hasFile('photo_before')) {
            $photoBeforePath = $request->file('photo_before')->store('pgj_photos', 'public');
        }

        $photoAfterPath = $pgj->photo_after;
        if ($request->hasFile('photo_after')) {
            $photoAfterPath = $request->file('photo_after')->store('pgj_photos', 'public');
        }

        // Rekonsiliasi material terpakai vs sisa dikembalikan ke gudang
        if (!empty($validated['materials'])) {
            $returnTransaction = null;

            foreach ($validated['materials'] as $matItem) {
                $pgjMat = PgjMaterial::find($matItem['pgj_material_id']);
                if ($pgjMat) {
                    $qtyUsed = (float) $matItem['quantity_used'];
                    $qtyPrepared = (float) $pgjMat->quantity_prepared;
                    $qtyReturned = max(0, $qtyPrepared - $qtyUsed);

                    $pgjMat->update([
                        'quantity_used' => $qtyUsed,
                        'quantity_returned' => $qtyReturned,
                    ]);

                    // Jika ada sisa material, kembalikan ke stok gudang
                    if ($qtyReturned > 0) {
                        if (!$returnTransaction) {
                            $returnTransaction = MaterialTransaction::create([
                                'transaction_number' => 'IN-PGJ-RET-' . date('YmdHis') . rand(10, 99),
                                'user_id' => auth()->id(),
                                'type' => 'in',
                                'status' => 'approved',
                                'technician_name' => auth()->user()->name,
                                'purpose' => "Pengembalian Sisa Material Jalur PGJ #{$pgj->ticket_number}",
                                'date' => now(),
                                'area_id' => $pgj->area_id,
                            ]);
                        }

                        $material = Material::find($pgjMat->material_id);
                        if ($material) {
                            $matStock = null;
                            if ($pgj->area_id) {
                                $matStock = \App\Models\MaterialStock::firstOrCreate(
                                    ['material_id' => $material->id, 'area_id' => $pgj->area_id],
                                    ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
                                );
                            }

                            $stockBefore = $matStock ? (float) $matStock->stock : (float) $material->stock;
                            $stockAfter = $stockBefore + $qtyReturned;

                            if ($matStock) {
                                $matStock->increment('stock', $qtyReturned);
                            }
                            $material->increment('stock', $qtyReturned);

                            $ppu = $material->selling_price ?: ($material->price_per_unit ?? 0);

                            MaterialTransactionItem::create([
                                'material_transaction_id' => $returnTransaction->id,
                                'material_id' => $material->id,
                                'quantity' => $qtyReturned,
                                'unit' => $pgjMat->unit ?? $material->unit ?? 'pcs',
                                'price_per_unit' => $ppu,
                                'total_price' => $ppu * $qtyReturned,
                                'stock_before' => $stockBefore,
                                'stock_after' => $stockAfter,
                                'condition' => 'Layak Pakai'
                            ]);
                        }
                    }
                }
            }
        }

        $oldStatus = $pgj->status;
        $pgj->update([
            'status' => 'completed',
            'action_taken' => $validated['action_taken'],
            'core_details' => $validated['core_details'] ?? null,
            'optical_loss_before' => $validated['optical_loss_before'] ?? null,
            'optical_loss_after' => $validated['optical_loss_after'] ?? null,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'photo_before' => $photoBeforePath,
            'photo_after' => $photoAfterPath,
            'resolution_notes' => $validated['resolution_notes'] ?? null,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        AuditLog::createLog(
            'Laporan Restorasi PGJ Selesai',
            $pgj,
            $oldStatus,
            'completed',
            'Teknisi melaporkan restorasi gangguan jalur selesai: ' . $validated['action_taken']
        );

        return redirect()->back()->with('success', 'Laporan restorasi gangguan jalur berhasil disimpan. Jalur dinyatakan normal kembali.');
    }

    /**
     * Cetak Berita Acara & SPK Restorasi Gangguan Jalur (PGJ)
     */
    public function print(PgjTicket $pgj)
    {
        $pgj->load([
            'area',
            'odc',
            'odp.odc',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'resolvedByUser',
        ]);

        return view('print.pgj-report', [
            'ticket' => $pgj,
        ]);
    }

    /**
     * Hapus Tiket PGJ
     */
    public function destroy(PgjTicket $pgj)
    {
        $num = $pgj->ticket_number;
        $pgj->technicians()->detach();
        $pgj->materials()->delete();
        $pgj->delete();

        AuditLog::createLog(
            'Hapus Tiket PGJ',
            null,
            null,
            null,
            "Menghapus tiket PGJ {$num}"
        );

        return redirect()->back()->with('success', "Tiket PGJ #{$num} berhasil dihapus.");
    }
}
