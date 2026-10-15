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

        return Inertia::render('Pgj/Index', [
            'tickets' => $tickets,
            'areas' => $areas,
            'odcs' => $odcs,
            'odps' => $odps,
            'technicians' => $technicians,
            'counts' => $counts,
            'filters' => $request->only(['search', 'status', 'priority', 'segment_type', 'area_id']),
        ]);
    }

    /**
     * Halaman Penjadwalan & Penyiapan Material Jalur (Kepala Teknisi)
     */
    public function jadwal(Request $request)
    {
        $query = PgjTicket::with([
            'area',
            'odc',
            'odp.odc',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
        ])->whereIn('status', ['escalated_to_lead', 'scheduled']);

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

        $materials = Material::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Pgj/Jadwal', [
            'tickets' => $tickets,
            'areas' => $areas,
            'technicians' => $technicians,
            'materials' => $materials,
            'filters' => $request->only(['search', 'area_id']),
        ]);
    }

    /**
     * Halaman Pelaporan Hasil Penanganan Jalur (Teknisi)
     */
    public function laporan(Request $request)
    {
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
        $validated = $request->validate([
            'escalation_reason' => 'required|string',
            'admin_notes' => 'nullable|string',
        ]);

        $oldStatus = $pgj->status;
        $pgj->update([
            'status' => 'escalated_to_lead',
            'escalation_reason' => $validated['escalation_reason'],
            'admin_notes' => $validated['admin_notes'] ?: $pgj->admin_notes,
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
        $validated = $request->validate([
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|string|max:100',
            'lead_notes' => 'nullable|string',
            'technician_ids' => 'required|array|min:1',
            'technician_ids.*' => 'exists:users,id',
            'materials' => 'nullable|array',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.quantity' => 'required|numeric|min:0.01',
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
        $pgj->technicians()->sync($validated['technician_ids']);

        // Kelola alokasi material gudang jalur
        if (!empty($validated['materials'])) {
            $transaction = MaterialTransaction::create([
                'user_id' => auth()->id(),
                'type' => 'out',
                'description' => "Material Jalur PGJ #{$pgj->ticket_number} ({$pgj->segment_type}: {$pgj->category})",
                'transaction_date' => now(),
            ]);

            foreach ($validated['materials'] as $matItem) {
                $material = Material::find($matItem['material_id']);
                $qty = (float) $matItem['quantity'];

                if ($material) {
                    $material->decrement('stock', $qty);

                    MaterialTransactionItem::create([
                        'material_transaction_id' => $transaction->id,
                        'material_id' => $material->id,
                        'quantity' => $qty,
                        'price' => $material->price ?? 0,
                    ]);

                    PgjMaterial::create([
                        'pgj_ticket_id' => $pgj->id,
                        'material_id' => $material->id,
                        'quantity_prepared' => $qty,
                        'quantity_used' => 0,
                        'quantity_returned' => 0,
                        'unit' => $material->unit,
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
                                'user_id' => auth()->id(),
                                'type' => 'in',
                                'description' => "Pengembalian Sisa Material Jalur PGJ #{$pgj->ticket_number}",
                                'transaction_date' => now(),
                            ]);
                        }

                        $material = Material::find($pgjMat->material_id);
                        if ($material) {
                            $material->increment('stock', $qtyReturned);

                            MaterialTransactionItem::create([
                                'material_transaction_id' => $returnTransaction->id,
                                'material_id' => $material->id,
                                'quantity' => $qtyReturned,
                                'price' => $material->price ?? 0,
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
