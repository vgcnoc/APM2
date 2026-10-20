<?php

namespace App\Http\Controllers;

use App\Models\PgsTicket;
use App\Models\PgsMaterial;
use App\Models\PgsMaterialRequest;
use App\Models\Customer;
use App\Models\Area;
use App\Models\User;
use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\AuditLog;
use App\Models\Ont;
use App\Services\DataScopeService;
use App\Services\RestockStockService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class PgsController extends Controller
{
    public function __construct()
    {
        // Ensure role 'kepala teknisi' exists
        try {
            Role::firstOrCreate(['name' => 'kepala teknisi']);
        } catch (\Throwable $e) {
            // ignore if table not migrated yet
        }
    }

    /**
     * Halaman Utama Penanganan Sigap (PGS) - Temuan & Penanganan Admin
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_pgs')) {
            if ($user->can('menu_pgs_laporan')) {
                return redirect()->route('pgs.laporan');
            }
            if ($user->can('menu_pgs_jadwal')) {
                return redirect()->route('pgs.jadwal');
            }
            abort(403, 'Akses ke modul PGS tidak diizinkan.');
        }

        $query = PgsTicket::with([
            'area',
            'customer.areaModel',
            'customer.package',
            'customer.ont.odp.odc',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'resolvedByUser'
        ]);

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyPgsScope($query);

        // Filter search
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('reported_issue', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('customer_code', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('address', 'like', "%{$search}%");
                  });
            });
        }

        // Filter status tab
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'completed') {
                $query->whereIn('status', ['completed', 'resolved_by_admin']);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter area
        if ($request->filled('area_id')) {
            $areaModel = Area::find($request->area_id);
            $areaName = $areaModel?->name;
            $query->where(function($q) use ($request, $areaName) {
                $q->where('area_id', $request->area_id)
                  ->orWhereHas('customer', function($cq) use ($request, $areaName) {
                      $cq->where('area_id', $request->area_id)
                         ->when($areaName, fn($sub) => $sub->orWhere('area', $areaName));
                  });
            });
        }

        // Scoping for technician
        if (auth()->check() && auth()->user()->hasRole('teknisi') && !auth()->user()->hasRole('admin') && !auth()->user()->hasRole('kepala teknisi')) {
            $query->where(function($q) {
                $q->whereHas('technicians', function($tq) {
                    $tq->where('users.id', auth()->id());
                })->orWhere('lead_technician_id', auth()->id());
            });
        }

        $tickets = $query->latest('id')->paginate($request->per_page ?? 15)->withQueryString();

        // Statistics / counts
        $counts = [
            'all' => PgsTicket::count(),
            'admin_handling' => PgsTicket::where('status', 'admin_handling')->count(),
            'escalated_to_lead' => PgsTicket::where('status', 'escalated_to_lead')->count(),
            'scheduled' => PgsTicket::where('status', 'scheduled')->count(),
            'in_progress' => PgsTicket::where('status', 'in_progress')->count(),
            'completed' => PgsTicket::whereIn('status', ['completed', 'resolved_by_admin'])->count(),
        ];

        $customers = Customer::whereIn('status', ['active', 'suspended', 'isolir', 'installing'])
            ->select('id', 'name', 'customer_code', 'phone', 'address', 'area_id', 'area', 'package_id')
            ->with(['package:id,name', 'areaModel:id,name', 'ont:id,customer_id,serial_number,status,rx_power,odp_id', 'ont.odp:id,name'])
            ->get();

        $technicians = User::role(['teknisi', 'kepala teknisi'])->select('id', 'name', 'phone')->get();
        if ($technicians->isEmpty()) {
            $technicians = User::select('id', 'name', 'phone')->get();
        }

        $leadTechnicians = User::role(['kepala teknisi', 'admin', 'noc', 'manager'])->select('id', 'name')->get();
        if ($leadTechnicians->isEmpty()) {
            $leadTechnicians = User::select('id', 'name')->get();
        }

        $materials = Material::select('id', 'name', 'category', 'unit', 'stock')->where('stock', '>', 0)->get();
        $areas = Area::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Pgs/Index', [
            'tickets' => $tickets,
            'counts' => $counts,
            'customers' => $customers,
            'areas' => $areas,
            'technicians' => $technicians,
            'leadTechnicians' => $leadTechnicians,
            'materials' => $materials,
            'filters' => $request->only(['search', 'status', 'priority', 'area_id']),
        ]);
    }

    /**
     * Halaman Khusus Kepala Teknisi: Penjadwalan & Penyiapan Material
     */
    public function jadwal(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_pgs_jadwal')) {
            if ($user->can('menu_pgs_laporan')) {
                return redirect()->route('pgs.laporan');
            }
            if ($user->can('menu_pgs')) {
                return redirect()->route('pgs.index');
            }
            abort(403, 'Akses ke Jadwal PGS tidak diizinkan.');
        }

        $query = PgsTicket::with([
            'area',
            'customer.areaModel',
            'customer.package',
            'customer.ont.odp',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'materialRequests.material',
            'materialRequests.requester',
            'materialRequests.reviewer',
            'preparedOnt',
            'oldOnt',
            'requestedByUser',
            'preparedByUser',
        ])->whereIn('status', ['escalated_to_lead', 'scheduled', 'in_progress']);

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyPgsScope($query);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('reported_issue', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('customer_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('area_id')) {
            $areaModel = Area::find($request->area_id);
            $areaName = $areaModel?->name;
            $query->where(function($q) use ($request, $areaName) {
                $q->where('area_id', $request->area_id)
                  ->orWhereHas('customer', function($cq) use ($request, $areaName) {
                      $cq->where('area_id', $request->area_id)
                         ->when($areaName, fn($sub) => $sub->orWhere('area', $areaName));
                  });
            });
        }

        $tickets = $query->latest('id')->paginate($request->per_page ?? 15)->withQueryString();

        $technicians = User::role(['teknisi', 'kepala teknisi'])->select('id', 'name', 'phone')->get();
        if ($technicians->isEmpty()) {
            $technicians = User::select('id', 'name', 'phone')->get();
        }

        $materials = Material::with('stocks')
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->orderBy('name')
            ->get();
        $materialTransactions = RestockStockService::getRestockTransactions();
        $areas = Area::select('id', 'name')->orderBy('name')->get();
        $availableOnts = Ont::whereNull('customer_id')
            ->whereIn('status', ['Sudah Set', 'Belum Set/Baru Input'])
            ->select('id', 'ont_id', 'serial_number', 'brand', 'model', 'status', 'area_id')
            ->orderBy('serial_number')
            ->get();

        return Inertia::render('Pgs/Jadwal', [
            'tickets' => $tickets,
            'areas' => $areas,
            'technicians' => $technicians,
            'materials' => $materials,
            'materialTransactions' => $materialTransactions,
            'availableOnts' => $availableOnts,
            'filters' => $request->only(['search', 'area_id']),
        ]);
    }

    /**
     * Halaman Khusus Teknisi: Laporan Hasil Penanganan
     */
    public function laporan(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can('menu_pgs_laporan')) {
            if ($user->can('menu_pgs')) {
                return redirect()->route('pgs.index');
            }
            if ($user->can('menu_pgs_jadwal')) {
                return redirect()->route('pgs.jadwal');
            }
            abort(403, 'Akses ke Laporan PGS tidak diizinkan.');
        }

        $query = PgsTicket::with([
            'area',
            'customer.areaModel',
            'customer.package',
            'customer.ont.odp',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'resolvedByUser',
            'materialRequests.material',
            'materialRequests.requester',
            'materialRequests.reviewer',
            'preparedOnt',
            'oldOnt',
            'requestedByUser',
            'preparedByUser',
        ])->whereIn('status', ['scheduled', 'in_progress', 'completed']);

        // Terapkan cakupan hak akses (all area, per area, karyawan sendiri)
        DataScopeService::applyPgsScope($query);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('action_taken', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('customer_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('area_id')) {
            $areaModel = Area::find($request->area_id);
            $areaName = $areaModel?->name;
            $query->where(function($q) use ($request, $areaName) {
                $q->where('area_id', $request->area_id)
                  ->orWhereHas('customer', function($cq) use ($request, $areaName) {
                      $cq->where('area_id', $request->area_id)
                         ->when($areaName, fn($sub) => $sub->orWhere('area', $areaName));
                  });
            });
        }

        $tickets = $query->latest('id')->paginate($request->per_page ?? 15)->withQueryString();

        $materials = Material::select('id', 'name', 'category', 'unit', 'stock')->get();
        $materialTransactions = RestockStockService::getRestockTransactions();
        $areas = Area::select('id', 'name')->orderBy('name')->get();
        $availableOnts = Ont::whereNull('customer_id')
            ->whereIn('status', ['Sudah Set', 'Belum Set/Baru Input'])
            ->select('id', 'ont_id', 'serial_number', 'brand', 'model', 'status', 'area_id')
            ->orderBy('serial_number')
            ->get();

        return Inertia::render('Pgs/Laporan', [
            'tickets' => $tickets,
            'areas' => $areas,
            'materials' => $materials,
            'materialTransactions' => $materialTransactions,
            'availableOnts' => $availableOnts,
            'filters' => $request->only(['search', 'area_id']),
        ]);
    }

    /**
     * Step 1: Admin membuat penanganan ketika menemukan gangguan di sistem
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user && !$user->can(['pgs_btn_create', 'pgs_manage']) && !$user->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat laporan PGS.');
        }

        $validated = $request->validate([
            'area_id' => 'nullable|exists:areas,id',
            'customer_id' => 'required|exists:customers,id',
            'category' => 'required|string|max:100',
            'priority' => 'required|in:low,medium,high,critical',
            'reported_issue' => 'required|string',
            'admin_notes' => 'nullable|string',
        ]);

        if (empty($validated['area_id'])) {
            $customer = Customer::find($validated['customer_id']);
            $validated['area_id'] = $customer?->area_id;
            if (empty($validated['area_id']) && $customer?->area) {
                $matchedArea = Area::where('name', $customer->area)->first();
                $validated['area_id'] = $matchedArea?->id;
            }
        }

        $validated['status'] = 'admin_handling';
        $validated['created_by'] = auth()->id();

        $ticket = PgsTicket::create($validated);

        AuditLog::createLog(
            'Buat Penanganan Sigap (PGS)',
            $ticket,
            null,
            'admin_handling',
            'Admin membuat tiket gangguan PGS untuk pelanggan ID: ' . $validated['customer_id']
        );

        return redirect()->back()->with('success', 'Tiket Penanganan Sigap (' . $ticket->ticket_number . ') berhasil dibuat.');
    }

    /**
     * Step 1B: Admin berhasil menyelesaikan gangguan secara langsung di sistem
     */
    public function resolveByAdmin(Request $request, PgsTicket $pgs)
    {
        $user = $request->user();
        if ($user && !$user->can(['pgs_btn_resolve', 'pgs_manage']) && !$user->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menyelesaikan gangguan PGS di sistem.');
        }

        $validated = $request->validate([
            'admin_notes' => 'required|string',
        ]);

        $oldStatus = $pgs->status;

        $pgs->update([
            'status' => 'resolved_by_admin',
            'admin_notes' => $validated['admin_notes'],
            'resolution_notes' => 'Diselesaikan langsung oleh Admin di sistem: ' . $validated['admin_notes'],
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        AuditLog::createLog(
            'PGS Selesai oleh Admin',
            $pgs,
            $oldStatus,
            'resolved_by_admin',
            'Admin menyelesaikan gangguan jarak jauh: ' . $validated['admin_notes']
        );

        return redirect()->back()->with('success', 'Gangguan berhasil diselesaikan langsung oleh Admin di sistem.');
    }

    /**
     * Step 1C: Admin tidak bisa menangani -> Diserahkan ke Kepala Teknisi
     */
    public function escalateToLead(Request $request, PgsTicket $pgs)
    {
        $user = $request->user();
        if ($user && !$user->can(['pgs_btn_escalate', 'pgs_manage']) && !$user->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menyerahkan tiket PGS ke Kepala Teknisi.');
        }

        $validated = $request->validate([
            'escalation_reason' => 'required|string',
            'lead_technician_id' => 'nullable|exists:users,id',
        ]);

        $oldStatus = $pgs->status;

        $pgs->update([
            'status' => 'escalated_to_lead',
            'escalated_at' => now(),
            'escalation_reason' => $validated['escalation_reason'],
            'lead_technician_id' => $validated['lead_technician_id'] ?? $pgs->lead_technician_id,
        ]);

        AuditLog::createLog(
            'PGS Diserahkan ke Kepala Teknisi',
            $pgs,
            $oldStatus,
            'escalated_to_lead',
            'Kendala tidak bisa ditangani di sistem, diserahkan ke Kepala Teknisi: ' . $validated['escalation_reason']
        );

        return redirect()->back()->with('success', 'Tiket PGS berhasil diserahkan ke Kepala Teknisi untuk dijadwalkan.');
    }

    /**
     * Step 2: Kepala Teknisi menjadwalkan penanganan ke teknisi & menyiapkan material
     */
    public function schedule(Request $request, PgsTicket $pgs)
    {
        $user = $request->user();
        if ($user && !$user->can(['pgs_btn_assign', 'pgs_manage']) && !$user->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menjadwalkan teknisi PGS.');
        }

        // Normalisasi parameter teknisi
        if ($request->has('technician_ids') && !$request->has('technicians')) {
            $request->merge(['technicians' => $request->technician_ids]);
        }
        if ($request->has('technicians') && !$request->has('technician_ids')) {
            $request->merge(['technician_ids' => $request->technicians]);
        }

        // Normalisasi parameter materials
        if ($request->has('materials') && is_array($request->materials)) {
            $mats = $request->materials;
            foreach ($mats as $k => $m) {
                if (isset($m['quantity']) && !isset($m['quantity_prepared'])) {
                    $mats[$k]['quantity_prepared'] = $m['quantity'];
                } elseif (isset($m['quantity_prepared']) && !isset($m['quantity'])) {
                    $mats[$k]['quantity'] = $m['quantity_prepared'];
                }
            }
            $request->merge(['materials' => $mats]);
        }

        $validated = $request->validate([
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|string|max:50',
            'technicians' => 'required_without:technician_ids|array|min:1',
            'technicians.*' => 'exists:users,id',
            'technician_ids' => 'sometimes|array',
            'technician_ids.*' => 'exists:users,id',
            'lead_notes' => 'nullable|string',
            'materials' => 'nullable|array',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.material_transaction_item_id' => 'nullable',
            'materials.*.trx_number' => 'nullable|string|max:100',
            'materials.*.quantity_prepared' => 'nullable|numeric|min:0.01',
            'materials.*.quantity' => 'nullable|numeric|min:0.01',
            'materials.*.unit' => 'nullable|string',
            'materials.*.notes' => 'nullable|string',
        ]);

        $oldStatus = $pgs->status;

        $pgs->update([
            'status' => 'scheduled',
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time' => $validated['scheduled_time'] ?? null,
            'lead_notes' => $validated['lead_notes'] ?? null,
            'lead_technician_id' => auth()->id(),
        ]);

        // Sync assigned technicians
        $techIds = $request->technicians ?? $request->technician_ids ?? [];
        $pgs->technicians()->sync($techIds);

        // Handle Material preparation & deduction
        if (!empty($validated['materials'])) {
            $techNames = User::whereIn('id', $techIds)->pluck('name')->join(', ');
            $areaId = $pgs->area_id ?? $pgs->customer?->area_id;

            // Kembalikan alokasi material sebelumnya ke stok area jika ini adalah revisi jadwal
            $previousMaterials = $pgs->materials()->get();
            foreach ($previousMaterials as $prevMat) {
                $prevQty = (float) $prevMat->quantity_prepared;
                if ($prevQty > 0) {
                    $orderItem = $prevMat->material_transaction_item_id 
                        ? MaterialTransactionItem::find($prevMat->material_transaction_item_id)
                        : null;
                    $targetAreaId = $areaId ?? $orderItem?->materialTransaction?->area_id ?? $orderItem?->transaction?->area_id;
                    if ($targetAreaId) {
                        $matStock = \App\Models\MaterialStock::where('material_id', $prevMat->material_id)
                            ->where('area_id', $targetAreaId)
                            ->first();
                        if ($matStock) {
                            $matStock->increment('stock', $prevQty);
                        }
                    }
                    Material::where('id', $prevMat->material_id)->increment('stock', $prevQty);
                }
            }

            // Hapus material prepared sebelumnya
            $pgs->materials()->delete();

            $sourceOrders = array_filter(array_unique(array_column($validated['materials'], 'trx_number')));
            $orderRefStr = !empty($sourceOrders) ? ' (Ref Order/SJ: ' . implode(', ', $sourceOrders) . ')' : '';

            // Buat transaksi material keluar
            $trans = MaterialTransaction::create([
                'transaction_number' => 'OUT-PGS-' . date('YmdHis') . rand(10, 99),
                'type' => 'out',
                'status' => 'approved',
                'date' => now(),
                'technician_name' => $techNames ?: auth()->user()->name,
                'purpose' => 'Penyiapan Material PGS: ' . $pgs->ticket_number . ' (' . ($pgs->customer?->name ?? '') . ')' . $orderRefStr,
                'source_type' => 'pgs',
                'source_id' => $pgs->id,
                'source_reference' => $pgs->ticket_number,
                'user_id' => auth()->id(),
                'customer_id' => $pgs->customer_id,
                'area_id' => $areaId,
            ]);

            foreach ($validated['materials'] as $matItem) {
                if (empty($matItem['material_id'])) continue;
                $material = Material::find($matItem['material_id']);
                if ($material) {
                    $qty = (float) ($matItem['quantity_prepared'] ?? $matItem['quantity'] ?? 0);
                    if ($qty <= 0) continue;

                    $trxItemId = $matItem['material_transaction_item_id'] ?? null;
                    $orderTrxItem = $trxItemId ? MaterialTransactionItem::with(['materialTransaction', 'transaction'])->find($trxItemId) : null;
                    $trxNumber = $matItem['trx_number'] ?? $orderTrxItem?->materialTransaction?->transaction_number ?? $orderTrxItem?->transaction?->transaction_number;
                    $targetAreaId = $areaId ?? $orderTrxItem?->materialTransaction?->area_id ?? $orderTrxItem?->transaction?->area_id;

                    $matStock = null;
                    if ($targetAreaId) {
                        $matStock = \App\Models\MaterialStock::where('material_id', $material->id)
                            ->where('area_id', $targetAreaId)
                            ->first();
                    }

                    $stockBefore = $matStock ? (float) $matStock->stock : (float) $material->stock;
                    $stockAfter = max(0, $stockBefore - $qty);

                    PgsMaterial::create([
                        'pgs_ticket_id' => $pgs->id,
                        'material_id' => $material->id,
                        'material_transaction_item_id' => $trxItemId,
                        'trx_number' => $trxNumber,
                        'quantity_prepared' => $qty,
                        'quantity_used' => $qty, // default awal sama, bisa disesuaikan teknisi saat lapor
                        'quantity_returned' => 0,
                        'unit' => $matItem['unit'] ?? $material->unit,
                        'notes' => $matItem['notes'] ?? null,
                    ]);

                    $unit = $matItem['unit'] ?? $material->unit;
                    $ppu = $material->selling_price ?: ($material->price_per_unit ?? 0);
                    if (strtolower($material->category ?? '') === 'kabel' && strtolower($unit) === 'meter') {
                        $mpr = $material->meter_per_roll > 0 ? $material->meter_per_roll : 1000;
                        $ppu = $ppu / $mpr;
                    }

                    MaterialTransactionItem::create([
                        'material_transaction_id' => $trans->id,
                        'material_id' => $material->id,
                        'quantity' => $qty,
                        'unit' => $unit,
                        'price_per_unit' => $ppu,
                        'total_price' => $ppu * $qty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                    ]);

                    // Potong stok dari Area dan Gudang Inventory
                    if ($matStock) {
                        $matStock->decrement('stock', $qty);
                    }
                    $material->decrement('stock', $qty);
                }
            }
        }

        AuditLog::createLog(
            'Penjadwalan & Material PGS',
            $pgs,
            $oldStatus,
            'scheduled',
            'Kepala Teknisi menjadwalkan penanganan ke teknisi dan menyiapkan material.'
        );

        return redirect()->back()->with('success', 'Penanganan berhasil dijadwalkan ke Teknisi dan material telah disiapkan.');
    }

    /**
     * Step 3: Teknisi membuat laporan penanganan PGS
     */
    public function submitReport(Request $request, PgsTicket $pgs)
    {
        $user = $request->user();
        if ($user && !$user->can(['pgs_btn_report', 'pgs_manage']) && !$user->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melaporkan hasil penanganan PGS.');
        }

        // Jika scope 'own' (teknisi biasa), verifikasi bahwa user memang ditugaskan pada tiket PGS ini
        if ($user && \App\Services\DataScopeService::getScopeLevel($user, 'pgs') === 'own') {
            $isAssigned = $pgs->technicians()->where('users.id', $user->id)->exists()
                || $pgs->created_by === $user->id
                || $pgs->lead_technician_id === $user->id;
            if (!$isAssigned) {
                abort(403, 'Anda hanya dapat melaporkan penanganan PGS yang ditugaskan kepada Anda.');
            }
        }

        $validated = $request->validate([
            'action_taken' => 'required|string',
            'optical_power_before' => 'nullable|string|max:50',
            'optical_power_after' => 'nullable|string|max:50',
            'start_time' => 'nullable|string|max:50',
            'end_time' => 'nullable|string|max:50',
            'resolution_notes' => 'nullable|string',
            'photo_before' => 'nullable|image|max:5120',
            'photo_after' => 'nullable|image|max:5120',
            'ont_replacement_action' => 'nullable|in:installed,cancelled',
            'ont_replacement_notes' => 'nullable|string',
            'materials' => 'nullable|array',
            'materials.*.id' => 'nullable',
            'materials.*.pgs_material_id' => 'nullable',
            'materials.*.material_id' => 'nullable|exists:materials,id',
            'materials.*.quantity_used' => 'nullable|numeric|min:0',
            'materials.*.quantity_returned' => 'nullable|numeric|min:0',
            'additional_materials' => 'nullable|array',
            'additional_materials.*.material_id' => 'required|exists:materials,id',
            'additional_materials.*.quantity' => 'required|numeric|min:0.01',
            'additional_materials.*.unit' => 'nullable|string',
            'additional_materials.*.notes' => 'nullable|string',
        ]);

        $photoBeforePath = $pgs->photo_before;
        if ($request->hasFile('photo_before')) {
            $photoBeforePath = $request->file('photo_before')->store('pgs_photos', 'public');
        }

        $photoAfterPath = $pgs->photo_after;
        if ($request->hasFile('photo_after')) {
            $photoAfterPath = $request->file('photo_after')->store('pgs_photos', 'public');
        }

        $areaId = $pgs->area_id ?? $pgs->customer?->area_id;

        // 1. Handle Rekonsiliasi Material Bekal Awal (Kembalikan sisa material yang tidak terpakai ke gudang)
        if (!empty($request->materials) && is_array($request->materials)) {
            $itemsToReturn = [];
            foreach ($request->materials as $matInput) {
                $pgsMatId = $matInput['id'] ?? $matInput['pgs_material_id'] ?? null;
                if (!$pgsMatId) continue;

                $pgsMat = PgsMaterial::find($pgsMatId);
                if ($pgsMat) {
                    $qtyUsed = (float) ($matInput['quantity_used'] ?? 0);
                    $prepQty = (float) $pgsMat->quantity_prepared;
                    $qtyReturned = isset($matInput['quantity_returned']) 
                        ? (float) $matInput['quantity_returned'] 
                        : max(0, $prepQty - $qtyUsed);

                    $pgsMat->update([
                        'quantity_used' => $qtyUsed,
                        'quantity_returned' => $qtyReturned,
                    ]);

                    // Jika ada sisa material yang dikembalikan, kumpulkan untuk serah terima ke gudang
                    if ($qtyReturned > 0) {
                        $itemsToReturn[] = [
                            'material_id' => $pgsMat->material_id,
                            'quantity' => $qtyReturned,
                            'unit' => $pgsMat->unit ?? 'pcs',
                            'condition' => 'Layak Pakai',
                        ];
                    }
                }
            }

            if (!empty($itemsToReturn)) {
                \App\Services\MaterialHandoverService::submitReturnHandover(
                    sourceType: 'pgs',
                    sourceId: $pgs->id,
                    sourceRef: $pgs->ticket_number,
                    items: $itemsToReturn,
                    technicianName: auth()->user()->name,
                    areaId: $areaId,
                    notes: 'Pengembalian Sisa Material PGS: ' . $pgs->ticket_number
                );
            }
        }

        // 2. Handle Permintaan / Material Tambahan di Lapangan (Extra Materials)
        if (!empty($request->additional_materials) && is_array($request->additional_materials)) {
            $extraItems = array_filter($request->additional_materials, function ($item) {
                return !empty($item['material_id']) && (float) ($item['quantity'] ?? 0) > 0;
            });

            if (!empty($extraItems)) {
                $outTrans = MaterialTransaction::create([
                    'transaction_number' => 'OUT-PGS-EXTRA-' . date('YmdHis') . rand(10, 99),
                    'type' => 'out',
                    'status' => 'approved',
                    'date' => now(),
                    'technician_name' => auth()->user()->name,
                    'purpose' => 'Material Tambahan Lapangan PGS: ' . $pgs->ticket_number . ' (' . ($pgs->customer?->name ?? '') . ')',
                    'source_type' => 'pgs',
                    'source_id' => $pgs->id,
                    'source_reference' => $pgs->ticket_number,
                    'request_type' => 'additional_material',
                    'user_id' => auth()->id(),
                    'customer_id' => $pgs->customer_id,
                    'area_id' => $areaId,
                ]);

                foreach ($extraItems as $extra) {
                    $matId = $extra['material_id'];
                    $extraQty = (float) $extra['quantity'];
                    $material = Material::find($matId);
                    if (!$material) continue;

                    $stockBefore = (float) $material->stock;
                    $stockAfter = max(0, $stockBefore - $extraQty);

                    $unit = $extra['unit'] ?? $material->unit;
                    $notes = !empty($extra['notes']) ? 'Tambahan: ' . $extra['notes'] : 'Material Tambahan di Lapangan';

                    // Catat ke PgsMaterial agar tampil di riwayat dan cetak SPK
                    PgsMaterial::create([
                        'pgs_ticket_id' => $pgs->id,
                        'material_id' => $material->id,
                        'quantity_prepared' => $extraQty,
                        'quantity_used' => $extraQty,
                        'quantity_returned' => 0,
                        'unit' => $unit,
                        'notes' => $notes,
                    ]);

                    $ppu = $material->selling_price ?: ($material->price_per_unit ?? 0);
                    if (strtolower($material->category ?? '') === 'kabel' && strtolower($unit) === 'meter') {
                        $mpr = $material->meter_per_roll > 0 ? $material->meter_per_roll : 1000;
                        $ppu = $ppu / $mpr;
                    }

                    MaterialTransactionItem::create([
                        'material_transaction_id' => $outTrans->id,
                        'material_id' => $material->id,
                        'quantity' => $extraQty,
                        'unit' => $unit,
                        'price_per_unit' => $ppu,
                        'total_price' => $ppu * $extraQty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                    ]);

                    // Kurangkan stok gudang
                    $material->decrement('stock', $extraQty);

                    // Kurangkan stok area jika ada
                    if ($areaId) {
                        $matStock = \App\Models\MaterialStock::where('material_id', $material->id)
                            ->where('area_id', $areaId)
                            ->first();
                        if ($matStock) {
                            $matStock->decrement('stock', $extraQty);
                        }
                    }
                }
            }
        }

        $oldStatus = $pgs->status;

        // 3. Handle Eksekusi Pergantian ONT (Jadi vs Tidak Jadi/Retur Gudang)
        $ontAction = $request->input('ont_replacement_action');
        $preparedOnt = $pgs->prepared_ont_id ? Ont::find($pgs->prepared_ont_id) : null;
        $ontSwapped = false;
        $ontReturned = false;

        if ($preparedOnt && ($pgs->ont_replacement_status === 'prepared' || $ontAction)) {
            if ($ontAction === 'installed') {
                $customer = $pgs->customer;
                $oldOnt = Ont::where('customer_id', $pgs->customer_id)
                    ->where('id', '!=', $preparedOnt->id)
                    ->first() ?: ($pgs->old_ont_id ? Ont::find($pgs->old_ont_id) : null);

                $odpId = $oldOnt?->odp_id;
                $portNum = $oldOnt?->port_number;
                $odpPortId = $oldOnt?->odp_port_id;

                // Lepas ONT lama pelanggan dan tandai rusak/ditarik
                if ($oldOnt) {
                    $oldOnt->update([
                        'customer_id' => null,
                        'odp_id' => null,
                        'port_number' => null,
                        'odp_port_id' => null,
                        'status' => 'rusak',
                        'description' => trim(($oldOnt->description ? $oldOnt->description . ' | ' : '') . 'Diganti dengan SN ' . $preparedOnt->serial_number . ' pada tiket PGS ' . $pgs->ticket_number . ' (' . now()->format('Y-m-d H:i') . ')'),
                    ]);
                }

                // Pasangkan ONT baru ke pelanggan
                $preparedOnt->update([
                    'customer_id' => $pgs->customer_id,
                    'area_id' => $customer?->area_id ?? $preparedOnt->area_id,
                    'odp_id' => $odpId ?? $preparedOnt->odp_id,
                    'port_number' => $portNum ?? $preparedOnt->port_number,
                    'odp_port_id' => $odpPortId ?? $preparedOnt->odp_port_id,
                    'status' => 'Sudah Set',
                    'description' => trim(($preparedOnt->description ? $preparedOnt->description . ' | ' : '') . 'Menggantikan SN ' . ($oldOnt?->serial_number ?? '-') . ' pada tiket PGS ' . $pgs->ticket_number),
                ]);

                $pgs->update([
                    'ont_replacement_status' => 'installed',
                    'ont_replacement_action' => 'installed',
                    'old_ont_id' => $oldOnt?->id,
                    'ont_replacement_notes' => $request->ont_replacement_notes ?? ('ONT berhasil diganti dengan unit baru SN: ' . $preparedOnt->serial_number),
                ]);
                $ontSwapped = true;
            } elseif ($ontAction === 'cancelled') {
                // Pergantian TIDAK JADI -> Kembalikan unit ONT baru ke Inventory Gudang
                $preparedOnt->update([
                    'customer_id' => null,
                    'status' => 'Sudah Set',
                ]);

                $pgs->update([
                    'ont_replacement_status' => 'cancelled',
                    'ont_replacement_action' => 'cancelled',
                    'ont_replacement_notes' => $request->ont_replacement_notes ?? 'Pergantian ONT tidak jadi saat penyelesaian, unit baru dikembalikan ke Inventory Gudang.',
                ]);
                $ontReturned = true;
            }
        }

        $pgs->update([
            'status' => 'completed',
            'action_taken' => $validated['action_taken'],
            'optical_power_before' => $validated['optical_power_before'] ?? null,
            'optical_power_after' => $validated['optical_power_after'] ?? null,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'photo_before' => $photoBeforePath,
            'photo_after' => $photoAfterPath,
            'resolution_notes' => $validated['resolution_notes'] ?? null,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        AuditLog::createLog(
            'Laporan Penanganan PGS Selesai',
            $pgs,
            $oldStatus,
            'completed',
            'Teknisi melaporkan penanganan PGS selesai: ' . $validated['action_taken']
        );

        $retCount = PgsMaterial::where('pgs_ticket_id', $pgs->id)->where('quantity_returned', '>', 0)->count();
        $msg = 'Laporan penanganan berhasil disimpan. Status tiket PGS telah selesai.';
        if ($ontSwapped) {
            $msg .= ' Unit ONT baru berhasil dipasangkan ke pelanggan.';
        } elseif ($ontReturned) {
            $msg .= ' Unit ONT baru telah dikembalikan ke Inventory Gudang.';
        }
        if ($retCount > 0) {
            $msg .= ' Sebanyak ' . $retCount . ' jenis kelebihan material otomatis dikembalikan ke stok gudang (Transaksi IN-PGS-RET).';
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Teknisi Lapangan mengajukan permintaan tambahan material ke Kepala Teknisi
     */
    public function requestMaterial(Request $request, PgsTicket $pgs)
    {
        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'nullable|string',
            'notes' => 'required|string', // Alasan kebutuhan darurat di lapangan
        ]);

        $material = Material::findOrFail($validated['material_id']);

        PgsMaterialRequest::create([
            'pgs_ticket_id' => $pgs->id,
            'material_id' => $material->id,
            'requested_by' => auth()->id(),
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'] ?? $material->unit,
            'notes' => $validated['notes'],
            'status' => 'pending',
        ]);

        AuditLog::createLog(
            'Permintaan Tambahan Material PGS',
            $pgs,
            'pending',
            'pending',
            'Teknisi ' . auth()->user()->name . ' meminta tambahan ' . $validated['quantity'] . ' ' . ($validated['unit'] ?? $material->unit) . ' ' . $material->name . '. Alasan: ' . $validated['notes']
        );

        return redirect()->back()->with('success', 'Permintaan tambahan material berhasil diajukan ke Kepala Teknisi.');
    }

    /**
     * Kepala Teknisi menyetujui permintaan tambahan material dari teknisi lapangan
     */
    public function approveMaterialRequest(Request $request, PgsTicket $pgs, PgsMaterialRequest $materialRequest)
    {
        if ($materialRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Permintaan material ini sudah diproses sebelumnya.');
        }

        $material = Material::findOrFail($materialRequest->material_id);
        $qty = (float) $materialRequest->quantity;
        $areaId = $pgs->area_id ?? $pgs->customer?->area_id;

        $matStock = null;
        if ($areaId) {
            $matStock = \App\Models\MaterialStock::where('material_id', $material->id)
                ->where('area_id', $areaId)
                ->first();
        }

        $stockBefore = $matStock ? (float) $matStock->stock : (float) $material->stock;
        $stockAfter = max(0, $stockBefore - $qty);

        // Catat transaksi pengeluaran barang
        $outTrans = MaterialTransaction::create([
            'transaction_number' => 'OUT-PGS-REQ-' . date('YmdHis') . rand(10, 99),
            'type' => 'out',
            'status' => 'approved',
            'date' => now(),
            'technician_name' => $materialRequest->requester?->name ?: auth()->user()->name,
            'purpose' => 'Persetujuan Material Tambahan PGS: ' . $pgs->ticket_number . ' (' . ($pgs->customer?->name ?? '') . ')',
            'user_id' => auth()->id(),
            'customer_id' => $pgs->customer_id,
            'area_id' => $areaId,
            'notes' => 'Disetujui Ka. Teknisi. Alasan teknisi: ' . ($materialRequest->notes ?? '-'),
        ]);

        $unit = $materialRequest->unit ?? $material->unit;
        $ppu = $material->selling_price ?: ($material->price_per_unit ?? 0);
        if (strtolower($material->category ?? '') === 'kabel' && strtolower($unit) === 'meter') {
            $mpr = $material->meter_per_roll > 0 ? $material->meter_per_roll : 1000;
            $ppu = $ppu / $mpr;
        }

        MaterialTransactionItem::create([
            'material_transaction_id' => $outTrans->id,
            'material_id' => $material->id,
            'quantity' => $qty,
            'unit' => $unit,
            'price_per_unit' => $ppu,
            'total_price' => $ppu * $qty,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
        ]);

        if ($matStock) {
            $matStock->decrement('stock', $qty);
        } else {
            $material->decrement('stock', $qty);
        }

        // Tambahkan ke pgs_materials (jika sudah ada, tambahkan quantity_prepared, jika belum buat baru)
        $existingPgsMat = PgsMaterial::where('pgs_ticket_id', $pgs->id)
            ->where('material_id', $material->id)
            ->first();

        if ($existingPgsMat) {
            $newPrep = (float) $existingPgsMat->quantity_prepared + $qty;
            $existingPgsMat->update([
                'quantity_prepared' => $newPrep,
                'quantity_used' => (float) $existingPgsMat->quantity_used + $qty,
                'notes' => trim(($existingPgsMat->notes ?? '') . ' | Tambahan disetujui: +' . $qty . ' ' . $unit),
            ]);
        } else {
            PgsMaterial::create([
                'pgs_ticket_id' => $pgs->id,
                'material_id' => $material->id,
                'quantity_prepared' => $qty,
                'quantity_used' => $qty,
                'quantity_returned' => 0,
                'unit' => $unit,
                'notes' => 'Tambahan Lapangan disetujui Ka. Teknisi: ' . ($materialRequest->notes ?? '-'),
            ]);
        }

        $materialRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        AuditLog::createLog(
            'Setujui Permintaan Material PGS',
            $pgs,
            'pending',
            'approved',
            'Kepala Teknisi menyetujui tambahan ' . $qty . ' ' . $unit . ' ' . $material->name . ' untuk teknisi ' . ($materialRequest->requester?->name ?? '-')
        );

        return redirect()->back()->with('success', 'Permintaan tambahan material disetujui dan ditambahkan ke penugasan teknisi.');
    }

    /**
     * Kepala Teknisi menolak permintaan tambahan material dari teknisi lapangan
     */
    public function rejectMaterialRequest(Request $request, PgsTicket $pgs, PgsMaterialRequest $materialRequest)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $materialRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        AuditLog::createLog(
            'Tolak Permintaan Material PGS',
            $pgs,
            'pending',
            'rejected',
            'Kepala Teknisi menolak permintaan material: ' . $validated['rejection_reason']
        );

        return redirect()->back()->with('success', 'Permintaan tambahan material telah ditolak.');
    }

    /**
     * Cetak Berita Acara / Surat Perintah Kerja Penanganan Sigap
     */
    public function print(PgsTicket $pgs)
    {
        $pgs->load([
            'area',
            'customer.areaModel',
            'customer.package',
            'customer.ont.odp.odc',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'resolvedByUser'
        ]);

        return view('print.pgs-report', [
            'ticket' => $pgs,
        ]);
    }

    /**
     * Batalkan tiket PGS
     */
    public function destroy(PgsTicket $pgs)
    {
        $oldStatus = $pgs->status;
        $pgs->update(['status' => 'canceled']);

        AuditLog::createLog(
            'Batalkan Tiket PGS',
            $pgs,
            $oldStatus,
            'canceled',
            'Tiket Penanganan Sigap dibatalkan.'
        );

        return redirect()->back()->with('success', 'Tiket Penanganan Sigap berhasil dibatalkan.');
    }

    /**
     * Teknisi Lapangan mengajukan permintaan pergantian ONT ke Kepala Teknisi
     */
    public function requestOnt(Request $request, PgsTicket $pgs)
    {
        $validated = $request->validate([
            'requested_ont_notes' => 'required|string|max:1000',
        ]);

        $oldOntId = $pgs->customer?->ont?->id;

        $pgs->update([
            'ont_replacement_status' => 'requested',
            'requested_ont_notes' => $validated['requested_ont_notes'],
            'requested_ont_at' => now(),
            'requested_by' => auth()->id(),
            'old_ont_id' => $oldOntId,
        ]);

        AuditLog::createLog(
            'Request Pergantian ONT PGS',
            $pgs,
            'none',
            'requested',
            'Teknisi mengajukan pergantian unit ONT: ' . $validated['requested_ont_notes']
        );

        return redirect()->back()->with('success', 'Permintaan pergantian ONT berhasil diajukan ke Kepala Teknisi.');
    }

    /**
     * Kepala Teknisi menerima permintaan & menyiapkan ONT baru dari inventory gudang
     */
    public function prepareOnt(Request $request, PgsTicket $pgs)
    {
        $user = $request->user();
        if ($user && !$user->can(['pgs_manage', 'pgs_btn_assign']) && !$user->hasRole(['admin', 'kepala teknisi'])) {
            abort(403, 'Hanya Kepala Teknisi atau Admin yang dapat menyiapkan ONT baru.');
        }

        $validated = $request->validate([
            'prepared_ont_id' => 'required|exists:onts,id',
        ]);

        $ont = Ont::findOrFail($validated['prepared_ont_id']);
        if (!empty($ont->customer_id)) {
            return redirect()->back()->with('error', 'ONT tersebut sudah terpasang pada pelanggan lain!');
        }

        // Tandai status ONT di inventory
        $ont->update([
            'status' => 'Disiapkan Teknisi',
        ]);

        $pgs->update([
            'ont_replacement_status' => 'prepared',
            'prepared_ont_id' => $ont->id,
            'prepared_ont_at' => now(),
            'prepared_by' => auth()->id(),
        ]);

        AuditLog::createLog(
            'Kepala Teknisi Menyiapkan ONT Baru PGS',
            $pgs,
            'requested',
            'prepared',
            'Kepala Teknisi menyiapkan unit ONT baru SN: ' . $ont->serial_number . ' (' . ($ont->brand ?? 'ONT') . ')'
        );

        return redirect()->back()->with('success', 'ONT baru berhasil disiapkan dari Inventory Gudang untuk teknisi lapangan.');
    }

    /**
     * Batalkan permintaan atau kembalikan ONT baru yang disiapkan ke inventory gudang
     */
    public function cancelOnt(Request $request, PgsTicket $pgs)
    {
        $notes = $request->input('notes', 'Pergantian ONT tidak jadi, unit dikembalikan ke Inventory Gudang.');

        if ($pgs->prepared_ont_id) {
            $preparedOnt = Ont::find($pgs->prepared_ont_id);
            if ($preparedOnt) {
                // Kembalikan ke persediaan gudang
                $preparedOnt->update([
                    'customer_id' => null,
                    'status' => 'Sudah Set',
                ]);
            }
        }

        $oldStatus = $pgs->ont_replacement_status;
        $pgs->update([
            'ont_replacement_status' => 'cancelled',
            'ont_replacement_action' => 'cancelled',
            'ont_replacement_notes' => $notes,
        ]);

        AuditLog::createLog(
            'Pergantian ONT PGS Dibatalkan & Retur Gudang',
            $pgs,
            $oldStatus,
            'cancelled',
            $notes
        );

        return redirect()->back()->with('success', 'Pergantian ONT dibatalkan. Unit ONT telah dikembalikan ke Inventory Gudang.');
    }
}

