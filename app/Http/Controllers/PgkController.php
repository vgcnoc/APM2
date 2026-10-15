<?php

namespace App\Http\Controllers;

use App\Models\PgkTicket;
use App\Models\PgkMaterial;
use App\Models\Customer;
use App\Models\Area;
use App\Models\User;
use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class PgkController extends Controller
{
    public function __construct()
    {
        // Pastikan role 'kepala teknisi' ada
        try {
            Role::firstOrCreate(['name' => 'kepala teknisi']);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Halaman Utama Penanganan Gangguan Komplain (PGK) - Penerimaan & Penanganan Admin
     */
    public function index(Request $request)
    {
        $query = PgkTicket::with([
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

        // Filter pencarian
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('complainant_name', 'like', "%{$search}%")
                  ->orWhere('complainant_phone', 'like', "%{$search}%")
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

        // Filter prioritas
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Filter saluran komplain
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
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

        // Filter hak akses teknisi (teknisi biasa hanya melihat tugasnya)
        if (auth()->check() && auth()->user()->hasRole('teknisi') && !auth()->user()->hasRole('admin') && !auth()->user()->hasRole('kepala teknisi')) {
            $query->where(function($q) {
                $q->whereHas('technicians', function($tq) {
                    $tq->where('users.id', auth()->id());
                })->orWhere('lead_technician_id', auth()->id());
            });
        }

        $tickets = $query->latest('id')->paginate($request->per_page ?? 15)->withQueryString();

        // Hitungan statistik
        $counts = [
            'all' => PgkTicket::count(),
            'admin_handling' => PgkTicket::where('status', 'admin_handling')->count(),
            'escalated_to_lead' => PgkTicket::where('status', 'escalated_to_lead')->count(),
            'scheduled' => PgkTicket::where('status', 'scheduled')->count(),
            'in_progress' => PgkTicket::where('status', 'in_progress')->count(),
            'completed' => PgkTicket::whereIn('status', ['completed', 'resolved_by_admin'])->count(),
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

        return Inertia::render('Pgk/Index', [
            'tickets' => $tickets,
            'counts' => $counts,
            'customers' => $customers,
            'areas' => $areas,
            'technicians' => $technicians,
            'leadTechnicians' => $leadTechnicians,
            'materials' => $materials,
            'filters' => $request->only(['search', 'status', 'priority', 'channel', 'area_id']),
        ]);
    }

    /**
     * Halaman Khusus Kepala Teknisi: Penjadwalan & Penyiapan Material PGK
     */
    public function jadwal(Request $request)
    {
        $query = PgkTicket::with([
            'area',
            'customer.areaModel',
            'customer.package',
            'customer.ont.odp',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
        ])->whereIn('status', ['escalated_to_lead', 'scheduled', 'in_progress']);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('complainant_name', 'like', "%{$search}%")
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

        $materials = Material::select('id', 'name', 'category', 'unit', 'stock')->where('stock', '>', 0)->get();
        $areas = Area::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Pgk/Jadwal', [
            'tickets' => $tickets,
            'areas' => $areas,
            'technicians' => $technicians,
            'materials' => $materials,
            'filters' => $request->only(['search', 'area_id']),
        ]);
    }

    /**
     * Halaman Khusus Teknisi: Laporan Hasil Penanganan PGK
     */
    public function laporan(Request $request)
    {
        $query = PgkTicket::with([
            'area',
            'customer.areaModel',
            'customer.package',
            'customer.ont.odp',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'resolvedByUser'
        ])->whereIn('status', ['scheduled', 'in_progress', 'completed']);

        // Teknisi hanya melihat tugas miliknya kecuali admin / kepala teknisi
        if (auth()->check() && auth()->user()->hasRole('teknisi') && !auth()->user()->hasRole('admin') && !auth()->user()->hasRole('kepala teknisi')) {
            $query->whereHas('technicians', function($tq) {
                $tq->where('users.id', auth()->id());
            });
        }

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('action_taken', 'like', "%{$search}%")
                  ->orWhere('complainant_name', 'like', "%{$search}%")
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
        $areas = Area::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Pgk/Laporan', [
            'tickets' => $tickets,
            'areas' => $areas,
            'materials' => $materials,
            'filters' => $request->only(['search', 'area_id']),
        ]);
    }

    /**
     * Step 1: Admin Menerima Komplain Pelanggan
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'area_id' => 'nullable|exists:areas,id',
            'customer_id' => 'required|exists:customers,id',
            'complainant_name' => 'nullable|string|max:150',
            'complainant_phone' => 'nullable|string|max:50',
            'channel' => 'required|string|in:whatsapp,telepon,datang_kantor,aplikasi,lainnya',
            'category' => 'required|string|max:100',
            'priority' => 'required|in:low,medium,high,critical',
            'reported_issue' => 'required|string',
            'admin_notes' => 'nullable|string',
        ]);

        $customer = Customer::find($validated['customer_id']);
        if (empty($validated['area_id'])) {
            $validated['area_id'] = $customer?->area_id;
            if (empty($validated['area_id']) && $customer?->area) {
                $matchedArea = Area::where('name', $customer->area)->first();
                $validated['area_id'] = $matchedArea?->id;
            }
        }

        if (empty($validated['complainant_name'])) {
            $validated['complainant_name'] = $customer?->name;
        }
        if (empty($validated['complainant_phone'])) {
            $validated['complainant_phone'] = $customer?->phone;
        }

        $validated['status'] = 'admin_handling';
        $validated['created_by'] = auth()->id();

        $ticket = PgkTicket::create($validated);

        AuditLog::createLog(
            'Penerimaan Komplain (PGK)',
            $ticket,
            null,
            'admin_handling',
            'Admin menerima komplain gangguan pelanggan ID: ' . $validated['customer_id'] . ' (' . ($ticket->complainant_name ?? '') . ')'
        );

        return redirect()->back()->with('success', 'Komplain Gangguan (' . $ticket->ticket_number . ') berhasil didaftarkan.');
    }

    /**
     * Step 1B: Admin berhasil menyelesaikan komplain jarak jauh di sistem
     */
    public function resolveByAdmin(Request $request, PgkTicket $pgk)
    {
        $validated = $request->validate([
            'admin_notes' => 'required|string',
        ]);

        $oldStatus = $pgk->status;

        $pgk->update([
            'status' => 'resolved_by_admin',
            'admin_notes' => $validated['admin_notes'],
            'resolution_notes' => 'Diselesaikan langsung oleh Admin di sistem: ' . $validated['admin_notes'],
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        AuditLog::createLog(
            'PGK Selesai oleh Admin',
            $pgk,
            $oldStatus,
            'resolved_by_admin',
            'Admin menyelesaikan komplain secara remote/sistem: ' . $validated['admin_notes']
        );

        return redirect()->back()->with('success', 'Komplain berhasil diselesaikan langsung oleh Admin di sistem.');
    }

    /**
     * Step 1C: Admin meneruskan komplain ke Kepala Teknisi untuk kunjungan lapangan
     */
    public function escalateToLead(Request $request, PgkTicket $pgk)
    {
        $validated = $request->validate([
            'escalation_reason' => 'required|string',
            'lead_technician_id' => 'nullable|exists:users,id',
        ]);

        $oldStatus = $pgk->status;

        $pgk->update([
            'status' => 'escalated_to_lead',
            'escalated_at' => now(),
            'escalation_reason' => $validated['escalation_reason'],
            'lead_technician_id' => $validated['lead_technician_id'] ?? $pgk->lead_technician_id,
        ]);

        AuditLog::createLog(
            'PGK Diteruskan ke Kepala Teknisi',
            $pgk,
            $oldStatus,
            'escalated_to_lead',
            'Komplain diteruskan ke Kepala Teknisi untuk dijadwalkan: ' . $validated['escalation_reason']
        );

        return redirect()->back()->with('success', 'Komplain PGK berhasil diteruskan ke Kepala Teknisi untuk dijadwalkan.');
    }

    /**
     * Step 2: Kepala Teknisi menjadwalkan penanganan ke teknisi & menyiapkan material
     */
    public function schedule(Request $request, PgkTicket $pgk)
    {
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
            'materials.*.quantity_prepared' => 'nullable|numeric|min:0.01',
            'materials.*.quantity' => 'nullable|numeric|min:0.01',
            'materials.*.unit' => 'nullable|string',
            'materials.*.notes' => 'nullable|string',
        ]);

        $oldStatus = $pgk->status;

        $pgk->update([
            'status' => 'scheduled',
            'scheduled_date' => $validated['scheduled_date'],
            'scheduled_time' => $validated['scheduled_time'] ?? null,
            'lead_notes' => $validated['lead_notes'] ?? null,
            'lead_technician_id' => auth()->id(),
        ]);

        // Hubungkan teknisi pelaksana
        $techIds = $request->technicians ?? $request->technician_ids ?? [];
        $pgk->technicians()->sync($techIds);

        // Penyiapan material dari gudang
        if (!empty($validated['materials'])) {
            $pgk->materials()->delete();

            $techNames = User::whereIn('id', $techIds)->pluck('name')->join(', ');
            $areaId = $pgk->area_id ?? $pgk->customer?->area_id;

            $trans = MaterialTransaction::create([
                'transaction_number' => 'OUT-PGK-' . date('YmdHis') . rand(10, 99),
                'type' => 'out',
                'status' => 'approved',
                'date' => now(),
                'technician_name' => $techNames ?: auth()->user()->name,
                'purpose' => 'Penyiapan Material PGK: ' . $pgk->ticket_number . ' (' . ($pgk->customer?->name ?? '') . ')',
                'user_id' => auth()->id(),
                'customer_id' => $pgk->customer_id,
                'area_id' => $areaId,
            ]);

            foreach ($validated['materials'] as $matItem) {
                if (empty($matItem['material_id'])) continue;
                $material = Material::find($matItem['material_id']);
                if ($material) {
                    $qty = (float) ($matItem['quantity_prepared'] ?? $matItem['quantity'] ?? 0);
                    if ($qty <= 0) continue;

                    $stockBefore = (float) $material->stock;
                    $stockAfter = max(0, $stockBefore - $qty);

                    PgkMaterial::create([
                        'pgk_ticket_id' => $pgk->id,
                        'material_id' => $material->id,
                        'quantity_prepared' => $qty,
                        'quantity_used' => $qty,
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

                    // Kurangkan stok area jika ada
                    if ($areaId) {
                        $matStock = \App\Models\MaterialStock::where('material_id', $material->id)
                            ->where('area_id', $areaId)
                            ->first();
                        if ($matStock) {
                            $matStock->decrement('stock', $qty);
                        }
                    }

                    // Kurangkan stok gudang
                    $material->decrement('stock', $qty);
                }
            }
        }

        AuditLog::createLog(
            'Penjadwalan & Material PGK',
            $pgk,
            $oldStatus,
            'scheduled',
            'Kepala Teknisi menjadwalkan kunjungan teknisi dan menyiapkan material penanganan komplain.'
        );

        return redirect()->back()->with('success', 'Penanganan komplain berhasil dijadwalkan ke Teknisi.');
    }

    /**
     * Step 3: Teknisi membuat laporan penanganan komplain
     */
    public function submitReport(Request $request, PgkTicket $pgk)
    {
        $validated = $request->validate([
            'action_taken' => 'required|string',
            'optical_power_before' => 'nullable|string|max:50',
            'optical_power_after' => 'nullable|string|max:50',
            'start_time' => 'nullable|string|max:50',
            'end_time' => 'nullable|string|max:50',
            'resolution_notes' => 'required|string',
            'photo_before' => 'nullable|image|max:4096',
            'photo_after' => 'nullable|image|max:4096',
            'materials' => 'nullable|array',
            'materials.*.id' => 'nullable|exists:pgk_materials,id',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.quantity_used' => 'required|numeric|min:0',
            'materials.*.quantity_returned' => 'nullable|numeric|min:0',
        ]);

        $photoBeforePath = $pgk->photo_before;
        if ($request->hasFile('photo_before')) {
            $photoBeforePath = $request->file('photo_before')->store('pgk_photos', 'public');
        }

        $photoAfterPath = $pgk->photo_after;
        if ($request->hasFile('photo_after')) {
            $photoAfterPath = $request->file('photo_after')->store('pgk_photos', 'public');
        }

        // Handle Material adjustments (kembalikan material jika ada yang tidak terpakai ke gudang)
        if (!empty($validated['materials'])) {
            foreach ($validated['materials'] as $matInput) {
                if (!empty($matInput['id'])) {
                    $pgkMat = PgkMaterial::find($matInput['id']);
                    if ($pgkMat) {
                        $qtyUsed = (float) $matInput['quantity_used'];
                        $qtyReturned = (float) ($matInput['quantity_returned'] ?? max(0, $pgkMat->quantity_prepared - $qtyUsed));

                        $pgkMat->update([
                            'quantity_used' => $qtyUsed,
                            'quantity_returned' => $qtyReturned,
                        ]);

                        // Jika ada material yang dikembalikan, tambahkan kembali ke stok gudang
                        if ($qtyReturned > 0) {
                            $material = Material::find($pgkMat->material_id);
                            if ($material) {
                                $stockBefore = (float) $material->stock;
                                $stockAfter = $stockBefore + $qtyReturned;

                                $returnTrans = MaterialTransaction::create([
                                    'transaction_number' => 'IN-PGK-RET-' . date('YmdHis') . rand(10, 99),
                                    'type' => 'in',
                                    'status' => 'approved',
                                    'date' => now(),
                                    'technician_name' => auth()->user()->name,
                                    'purpose' => 'Pengembalian Sisa Material PGK: ' . $pgk->ticket_number,
                                    'user_id' => auth()->id(),
                                    'customer_id' => $pgk->customer_id,
                                    'area_id' => $pgk->area_id ?? $pgk->customer?->area_id,
                                ]);

                                $unitRet = $pgkMat->unit ?? $material->unit;
                                $ppuRet = $material->selling_price ?: ($material->price_per_unit ?? 0);
                                if (strtolower($material->category ?? '') === 'kabel' && strtolower($unitRet) === 'meter') {
                                    $mpr = $material->meter_per_roll > 0 ? $material->meter_per_roll : 1000;
                                    $ppuRet = $ppuRet / $mpr;
                                }

                                MaterialTransactionItem::create([
                                    'material_transaction_id' => $returnTrans->id,
                                    'material_id' => $material->id,
                                    'quantity' => $qtyReturned,
                                    'unit' => $unitRet,
                                    'price_per_unit' => $ppuRet,
                                    'total_price' => $ppuRet * $qtyReturned,
                                    'stock_before' => $stockBefore,
                                    'stock_after' => $stockAfter,
                                ]);

                                $material->increment('stock', $qtyReturned);
                            }
                        }
                    }
                }
            }
        }

        $oldStatus = $pgk->status;

        $pgk->update([
            'status' => 'completed',
            'action_taken' => $validated['action_taken'],
            'optical_power_before' => $validated['optical_power_before'] ?? null,
            'optical_power_after' => $validated['optical_power_after'] ?? null,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'photo_before' => $photoBeforePath,
            'photo_after' => $photoAfterPath,
            'resolution_notes' => $validated['resolution_notes'],
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        AuditLog::createLog(
            'Laporan Penanganan PGK Selesai',
            $pgk,
            $oldStatus,
            'completed',
            'Teknisi melaporkan penanganan komplain selesai: ' . $validated['action_taken']
        );

        return redirect()->back()->with('success', 'Laporan penanganan komplain berhasil disimpan. Status tiket PGK telah selesai.');
    }

    /**
     * Cetak Surat Perintah Kerja (SPK) & Berita Acara PGK
     */
    public function print(PgkTicket $pgk)
    {
        $pgk->load([
            'customer.areaModel',
            'customer.package',
            'customer.ont.odp',
            'area',
            'creator',
            'leadTechnician',
            'technicians',
            'materials.material',
            'resolvedByUser',
        ]);

        return view('print.pgk-report', [
            'ticket' => $pgk,
        ]);
    }

    /**
     * Hapus Tiket PGK
     */
    public function destroy(PgkTicket $pgk)
    {
        $num = $pgk->ticket_number;
        $pgk->technicians()->detach();
        $pgk->materials()->delete();
        $pgk->delete();

        AuditLog::createLog(
            'Hapus Tiket PGK',
            null,
            null,
            null,
            'Tiket PGK ' . $num . ' dihapus oleh user: ' . auth()->user()->name
        );

        return redirect()->back()->with('success', 'Tiket PGK ' . $num . ' berhasil dihapus.');
    }
}
