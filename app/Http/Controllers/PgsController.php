<?php

namespace App\Http\Controllers;

use App\Models\PgsTicket;
use App\Models\PgsMaterial;
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
            $query->where(function($q) use ($request) {
                $q->where('area_id', $request->area_id)
                  ->orWhereHas('customer', function($cq) use ($request) {
                      $cq->where('area_id', $request->area_id);
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
            ->select('id', 'name', 'customer_code', 'phone', 'address', 'area_id', 'package_id')
            ->with(['package:id,name', 'ont:id,customer_id,serial_number,status,rx_power,odp_id', 'ont.odp:id,name'])
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
        $query = PgsTicket::with([
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
                  ->orWhere('reported_issue', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('customer_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('area_id')) {
            $query->where(function($q) use ($request) {
                $q->where('area_id', $request->area_id)
                  ->orWhereHas('customer', function($cq) use ($request) {
                      $cq->where('area_id', $request->area_id);
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

        return Inertia::render('Pgs/Jadwal', [
            'tickets' => $tickets,
            'areas' => $areas,
            'technicians' => $technicians,
            'materials' => $materials,
            'filters' => $request->only(['search', 'area_id']),
        ]);
    }

    /**
     * Halaman Khusus Teknisi: Laporan Hasil Penanganan
     */
    public function laporan(Request $request)
    {
        $query = PgsTicket::with([
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
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('customer_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('area_id')) {
            $query->where(function($q) use ($request) {
                $q->where('area_id', $request->area_id)
                  ->orWhereHas('customer', function($cq) use ($request) {
                      $cq->where('area_id', $request->area_id);
                  });
            });
        }

        $tickets = $query->latest('id')->paginate($request->per_page ?? 15)->withQueryString();

        $materials = Material::select('id', 'name', 'category', 'unit', 'stock')->get();
        $areas = Area::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Pgs/Laporan', [
            'tickets' => $tickets,
            'areas' => $areas,
            'materials' => $materials,
            'filters' => $request->only(['search', 'area_id']),
        ]);
    }

    /**
     * Step 1: Admin membuat penanganan ketika menemukan gangguan di sistem
     */
    public function store(Request $request)
    {
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
        $validated = $request->validate([
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|string|max:50',
            'technicians' => 'required|array|min:1',
            'technicians.*' => 'exists:users,id',
            'lead_notes' => 'nullable|string',
            'materials' => 'nullable|array',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.quantity_prepared' => 'required|numeric|min:0.01',
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
        $pgs->technicians()->sync($validated['technicians']);

        // Handle Material preparation & deduction
        if (!empty($validated['materials'])) {
            // Hapus material prepared sebelumnya jika ada revisi jadwal
            $pgs->materials()->delete();

            $techNames = User::whereIn('id', $validated['technicians'])->pluck('name')->join(', ');

            // Buat transaksi material keluar
            $trans = MaterialTransaction::create([
                'transaction_number' => 'OUT-PGS-' . date('YmdHis') . rand(10, 99),
                'type' => 'out',
                'status' => 'completed',
                'date' => now(),
                'technician_name' => $techNames ?: auth()->user()->name,
                'purpose' => 'Penyiapan Material PGS: ' . $pgs->ticket_number . ' (' . ($pgs->customer?->name ?? '') . ')',
                'user_id' => auth()->id(),
                'customer_id' => $pgs->customer_id,
            ]);

            foreach ($validated['materials'] as $matItem) {
                $material = Material::find($matItem['material_id']);
                if ($material) {
                    $qty = (float) $matItem['quantity_prepared'];
                    $stockBefore = (float) $material->stock;
                    $stockAfter = max(0, $stockBefore - $qty);

                    PgsMaterial::create([
                        'pgs_ticket_id' => $pgs->id,
                        'material_id' => $material->id,
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

                    // Kurangkan stok gudang
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
     * Step 3: Teknisi membuat laporan penanganan
     */
    public function submitReport(Request $request, PgsTicket $pgs)
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
            'materials.*.id' => 'nullable|exists:pgs_materials,id',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.quantity_used' => 'required|numeric|min:0',
            'materials.*.quantity_returned' => 'nullable|numeric|min:0',
        ]);

        $photoBeforePath = $pgs->photo_before;
        if ($request->hasFile('photo_before')) {
            $photoBeforePath = $request->file('photo_before')->store('pgs_photos', 'public');
        }

        $photoAfterPath = $pgs->photo_after;
        if ($request->hasFile('photo_after')) {
            $photoAfterPath = $request->file('photo_after')->store('pgs_photos', 'public');
        }

        // Handle Material adjustments (kembalikan material jika ada yang tidak terpakai)
        if (!empty($validated['materials'])) {
            foreach ($validated['materials'] as $matInput) {
                if (!empty($matInput['id'])) {
                    $pgsMat = PgsMaterial::find($matInput['id']);
                    if ($pgsMat) {
                        $qtyUsed = (float) $matInput['quantity_used'];
                        $qtyReturned = (float) ($matInput['quantity_returned'] ?? max(0, $pgsMat->quantity_prepared - $qtyUsed));

                        $pgsMat->update([
                            'quantity_used' => $qtyUsed,
                            'quantity_returned' => $qtyReturned,
                        ]);

                        // Jika ada material yang dikembalikan (tidak terpakai), kembalikan ke stok gudang
                        if ($qtyReturned > 0) {
                            $material = Material::find($pgsMat->material_id);
                            if ($material) {
                                $material->increment('stock', $qtyReturned);

                                // Catat transaksi in untuk pengembalian
                                $inTrans = MaterialTransaction::create([
                                    'transaction_number' => 'IN-PGS-' . date('YmdHis') . rand(10, 99),
                                    'type' => 'in',
                                    'status' => 'completed',
                                    'date' => now(),
                                    'technician_name' => auth()->user()->name,
                                    'purpose' => 'Pengembalian Sisa Material PGS ' . $pgs->ticket_number,
                                    'user_id' => auth()->id(),
                                    'customer_id' => $pgs->customer_id,
                                ]);

                                $unitRet = $pgsMat->unit ?? $material->unit;
                                $ppuRet = $material->selling_price ?: ($material->price_per_unit ?? 0);
                                if (strtolower($material->category ?? '') === 'kabel' && strtolower($unitRet) === 'meter') {
                                    $mpr = $material->meter_per_roll > 0 ? $material->meter_per_roll : 1000;
                                    $ppuRet = $ppuRet / $mpr;
                                }

                                MaterialTransactionItem::create([
                                    'material_transaction_id' => $inTrans->id,
                                    'material_id' => $material->id,
                                    'quantity' => $qtyReturned,
                                    'unit' => $unitRet,
                                    'price_per_unit' => $ppuRet,
                                    'total_price' => $ppuRet * $qtyReturned,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        $oldStatus = $pgs->status;

        $pgs->update([
            'status' => 'completed',
            'action_taken' => $validated['action_taken'],
            'optical_power_before' => $validated['optical_power_before'] ?? $pgs->optical_power_before,
            'optical_power_after' => $validated['optical_power_after'] ?? $pgs->optical_power_after,
            'start_time' => $validated['start_time'] ?? $pgs->start_time,
            'end_time' => $validated['end_time'] ?? $pgs->end_time,
            'resolution_notes' => $validated['resolution_notes'],
            'photo_before' => $photoBeforePath,
            'photo_after' => $photoAfterPath,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        AuditLog::createLog(
            'Laporan Penanganan PGS Selesai',
            $pgs,
            $oldStatus,
            'completed',
            'Teknisi telah menyelesaikan penanganan dan menyerahkan laporan: ' . $validated['action_taken']
        );

        return redirect()->back()->with('success', 'Laporan penanganan berhasil disimpan dan gangguan dinyatakan selesai.');
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
}
