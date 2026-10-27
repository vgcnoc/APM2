<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\InfrastructureWorkOrder;
use App\Models\InfrastructureWorkOrderMaterial;
use App\Models\InfrastructureWorkOrderMaterialRequest;
use App\Models\Material;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\MaterialStock;
use App\Models\Odc;
use App\Models\Olt;
use App\Models\User;
use App\Services\InfrastructureWorkOrderService;
use App\Services\DataScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InfrastructureWorkOrderController extends Controller
{
    /**
     * Dashboard & Daftar Penugasan Pembangunan ODC & ODP.
     */
    public function index(Request $request): Response
    {
        $query = InfrastructureWorkOrder::with(['area', 'olt', 'parentOdc', 'technician', 'technicians', 'creator']);

        // Filter Hak Akses Scoping (All, Area, Assigned)
        if (Auth::check() && !Auth::user()->hasRole('admin') && !Auth::user()->can('infrastructure_work_orders_view_all')) {
            if (Auth::user()->can('infrastructure_work_orders_view_area')) {
                $query->whereIn('area_id', Auth::user()->getAccessibleAreaIds());
            } else {
                $query->where(function ($q) {
                    $q->where('technician_id', Auth::id())
                      ->orWhereHas('technicians', fn($t) => $t->where('users.id', Auth::id()));
                });
            }
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('wo_number', 'like', "%{$request->search}%")
                  ->orWhere('target_name', 'like', "%{$request->search}%")
                  ->orWhere('location', 'like', "%{$request->search}%");
            });
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->device_type) {
            $query->where('device_type', $request->device_type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->area_id) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->technician_id) {
            $query->where(function ($q) use ($request) {
                $q->where('technician_id', $request->technician_id)
                  ->orWhereHas('technicians', fn($t) => $t->where('users.id', $request->technician_id));
            });
        }

        $workOrders = $query->latest('id')->paginate(15)->withQueryString();

        // KPI Ringkasan Status
        $kpiQuery = InfrastructureWorkOrder::query();
        if (Auth::check() && !Auth::user()->hasRole('admin') && !Auth::user()->can('infrastructure_work_orders_view_all')) {
            if (Auth::user()->can('infrastructure_work_orders_view_area')) {
                $kpiQuery->whereIn('area_id', Auth::user()->getAccessibleAreaIds());
            } else {
                $kpiQuery->where(function ($q) {
                    $q->where('technician_id', Auth::id())
                      ->orWhereHas('technicians', fn($t) => $t->where('users.id', Auth::id()));
                });
            }
        }

        $kpi = [
            'total' => (clone $kpiQuery)->count(),
            'pending_material' => (clone $kpiQuery)->where('status', 'pending_material')->count(),
            'material_ready' => (clone $kpiQuery)->where('status', 'material_ready')->count(),
            'in_progress' => (clone $kpiQuery)->where('status', 'in_progress')->count(),
            'under_review' => (clone $kpiQuery)->where('status', 'under_review')->count(),
            'completed' => (clone $kpiQuery)->where('status', 'completed')->count(),
        ];

        return Inertia::render('Infrastructure/WorkOrders/Index', [
            'workOrders' => $workOrders,
            'kpi' => $kpi,
            'areas' => DataScopeService::getScopedAreas(Auth::user(), 'infrastructure_work_orders'),
            'technicians' => $this->getEligibleTechnicians(),
            'filters' => $request->only(['search', 'type', 'device_type', 'status', 'area_id', 'technician_id']),
        ]);
    }

    /**
     * Ambil daftar personil teknisi & tim serbu pelaksana rollout.
     */
    protected function getEligibleTechnicians()
    {
        return User::where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        })->orderBy('name')->get(['id', 'name', 'phone', 'role']);
    }

    /**
     * Form Pembuatan Penugasan Baru (Smart Wizard & Unified Flow).
     */
    public function create(Request $request): Response
    {
        if (!Auth::user()->can('infrastructure_work_orders_create') && !Auth::user()->can('network_odc_create') && !Auth::user()->can('network_odp_create') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat penugasan pembangunan ODC/ODP.');
        }

        $materials = Material::where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->with('stocks')
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'stock', 'price_per_unit', 'category']);

        $materialTransactions = MaterialTransaction::where('type', 'in')
            ->with(['items.material.stocks', 'areaModel'])
            ->latest('id')
            ->limit(200)
            ->get();

        $user = Auth::user();
        $oltQuery = Olt::where('status', 'active');
        DataScopeService::applyOltScope($oltQuery, $user);

        $odcQuery = Odc::with('olt')->where('status', 'active');
        DataScopeService::applyOdcScope($odcQuery, $user);

        // Pre-fill parameter jika dibuka dari menu Data ODC, Data ODP, atau Monitoring Kapasitas
        $initialData = [
            'type' => $request->query('type') ?: ($request->query('device_type') === 'odc' ? 'odc_deployment' : 'odp_deployment'),
            'area_id' => $request->query('area_id') ? (int) $request->query('area_id') : null,
            'odc_id' => $request->query('odc_id') ? (int) $request->query('odc_id') : null,
            'olt_id' => $request->query('olt_id') ? (int) $request->query('olt_id') : null,
            'device_type' => $request->query('device_variant') ?: 'Normal',
        ];

        return Inertia::render('Infrastructure/WorkOrders/Create', [
            'areas' => DataScopeService::getScopedAreas($user, 'infrastructure_work_orders'),
            'olts' => $oltQuery->get(['id', 'name', 'area_id', 'total_pon_ports', 'pon_vlans']),
            'odcs' => $odcQuery->get(['id', 'name', 'area_id', 'capacity', 'olt_id', 'pon_port', 'splitter_ratio']),
            'technicians' => $this->getEligibleTechnicians(),
            'materials' => $materials,
            'materialTransactions' => $materialTransactions,
            'initialData' => $initialData,
        ]);
    }

    /**
     * API: Ambil Rekomendasi BOM (Bill of Materials) Otomatis.
     */
    public function getBOM(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:odc_deployment,odp_deployment',
            'device_type' => 'nullable|string|max:50',
            'capacity' => 'required|integer|min:1',
            'cable_length' => 'nullable|integer|min:0',
            'area_id' => 'nullable|exists:areas,id',
        ]);

        $bom = InfrastructureWorkOrderService::getSuggestedBOM(
            $validated['type'],
            (int) $validated['capacity'],
            (int) ($validated['cable_length'] ?? 150),
            $validated['area_id'] ?? null,
            $validated['device_type'] ?? null
        );

        return response()->json($bom);
    }

    /**
     * Simpan Work Order Baru / Langsung Aktifkan ke Data ODC & ODP.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('infrastructure_work_orders_create') && !Auth::user()->can('network_odc_create') && !Auth::user()->can('network_odp_create') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat penugasan pembangunan ODC/ODP.');
        }

        // Normalisasi input string kosong menjadi null
        foreach (['odc_id', 'olt_id', 'pon_port', 'odc_splitter_port', 'planned_latitude', 'planned_longitude', 'location', 'start_point', 'end_point', 'cable_pull', 'notes', 'splitter_ratio', 'core_capacity'] as $f) {
            if ($request->has($f) && ($request->$f === '' || $request->$f === 'null' || $request->$f === null)) {
                $request->merge([$f => null]);
            }
        }

        // Auto-fallback jika target_name kosong
        if (empty($request->target_name)) {
            $prefix = ($request->type === 'odc_deployment') ? 'ODC' : 'ODP';
            $area = Area::find($request->area_id);
            $areaCode = $area ? strtoupper(substr($area->name, 0, 3)) : 'DST';
            $request->merge(['target_name' => "{$prefix}-{$areaCode}-" . date('His')]);
        }

        // Auto-fallback jika technician_ids kosong
        if (empty($request->technician_ids)) {
            if (!empty($request->technician_id)) {
                $request->merge(['technician_ids' => [(int) $request->technician_id]]);
            } else {
                $firstTech = $this->getEligibleTechnicians()->first()?->id ?? Auth::id();
                $request->merge([
                    'technician_ids' => [$firstTech],
                    'technician_id' => $firstTech,
                ]);
            }
        }

        $validated = $request->validate([
            'type' => 'required|in:odc_deployment,odp_deployment',
            'device_type' => 'nullable|string|max:50',
            'target_name' => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id',
            'olt_id' => 'nullable|exists:olts,id',
            'pon_port' => 'nullable|integer|min:1',
            'odc_id' => 'nullable|exists:odcs,id',
            'odc_splitter_port' => 'nullable|integer|min:1',
            'capacity' => 'required|integer|min:1',
            'core_capacity' => 'nullable|integer|min:1',
            'splitter_ratio' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:500',
            'planned_latitude' => 'nullable|numeric|between:-90,90',
            'planned_longitude' => 'nullable|numeric|between:-180,180',
            'technician_id' => 'nullable|exists:users,id',
            'technician_ids' => 'required|array|min:1',
            'technician_ids.*' => 'exists:users,id',
            'scheduled_date' => 'required|date',
            'start_point' => 'nullable|string|max:255',
            'end_point' => 'nullable|string|max:255',
            'cable_pull' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
            'materials' => 'required|array|min:1',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.material_transaction_item_id' => 'nullable|integer',
            'materials.*.trx_number' => 'nullable|string|max:100',
            'materials.*.quantity' => 'required|numeric|min:0.01',
            'materials.*.unit' => 'nullable|string|max:20',
            'materials.*.note' => 'nullable|string|max:255',
            'materials.*.notes' => 'nullable|string|max:255',
        ]);

        $workOrder = InfrastructureWorkOrderService::createWorkOrder(
            $validated,
            $validated['materials'],
            Auth::user()
        );

        // Jika user memilih aksi langsung aktifkan ke master data ODC / ODP & GIS
        if ($request->boolean('direct_commission') || $request->input('action') === 'direct_commission') {
            InfrastructureWorkOrderService::approveAndCommission($workOrder, Auth::user());
            $targetRoute = ($workOrder->type === 'odc_deployment') ? 'odcs.index' : 'odps.index';
            return redirect()->route($targetRoute, ['search' => $workOrder->target_name])
                ->with('success', "Perangkat {$workOrder->target_name} berhasil dibangun dan langsung terdaftar aktif di Master Data & GIS!");
        }

        return redirect()->route('infrastructure.work-orders.show', $workOrder->id)
            ->with('success', "Penugasan {$workOrder->wo_number} ({$workOrder->target_name}) berhasil diterbitkan!");
    }

    /**
     * Detail Work Order & Lembar Kerja Lapangan.
     */
    public function show(InfrastructureWorkOrder $workOrder): Response
    {
        $workOrder->load([
            'area',
            'olt',
            'parentOdc',
            'technician',
            'technicians',
            'creator',
            'approver',
            'materials.material',
            'opticalChecks',
            'createdOdc',
            'createdOdp',
            'materialRequests.material',
            'materialRequests.requester',
            'materialRequests.reviewer',
        ]);

        $materials = Material::select('id', 'name', 'unit', 'stock', 'category')
            ->orderBy('name')
            ->get();

        return Inertia::render('Infrastructure/WorkOrders/Show', [
            'workOrder' => $workOrder,
            'materials' => $materials,
        ]);
    }

    /**
     * Update Informasi & Parameter Penugasan (Penugasan Teknisi, Tanggal, dll).
     */
    public function update(Request $request, InfrastructureWorkOrder $workOrder)
    {
        if (!Auth::user()->can('infrastructure_work_orders_edit') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengedit penugasan pembangunan ODC/ODP.');
        }

        $validated = $request->validate([
            'target_name' => 'sometimes|required|string|max:255',
            'device_type' => 'nullable|string|max:50',
            'capacity' => 'nullable|integer|min:1',
            'core_capacity' => 'nullable|integer|min:1',
            'splitter_ratio' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:500',
            'planned_latitude' => 'nullable|numeric|between:-90,90',
            'planned_longitude' => 'nullable|numeric|between:-180,180',
            'technician_id' => 'nullable|exists:users,id',
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
            'scheduled_date' => 'nullable|date',
            'start_point' => 'nullable|string|max:255',
            'end_point' => 'nullable|string|max:255',
            'cable_pull' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ]);

        $workOrder->update(collect($validated)->except('technician_ids')->toArray());

        if ($request->has('technician_ids') && is_array($request->technician_ids)) {
            $syncData = [];
            $leadId = $validated['technician_id'] ?? $request->technician_ids[0] ?? null;
            foreach ($request->technician_ids as $tid) {
                $syncData[$tid] = [
                    'role' => ((int)$tid === (int)$leadId) ? 'lead_splicer' : 'assistant_technician',
                ];
            }
            $workOrder->technicians()->sync($syncData);
        }

        return redirect()->back()->with('success', "Penugasan {$workOrder->wo_number} berhasil diperbarui.");
    }

    /**
     * Tampilan Cetak Surat Jalan Pengeluaran Material.
     */
    public function printSuratJalan(InfrastructureWorkOrder $workOrder)
    {
        $workOrder->load(['area', 'olt', 'parentOdc', 'technician', 'technicians', 'creator', 'materials.material']);

        return Inertia::render('Infrastructure/WorkOrders/PrintSuratJalan', [
            'workOrder' => $workOrder,
        ]);
    }

    /**
     * Gudang / Ka. Teknisi / Maintenance: Konfirmasi Pengeluaran Material & Cetak Surat Jalan.
     */
    public function approveMaterial(InfrastructureWorkOrder $workOrder)
    {
        if (!Auth::user()->can('infrastructure_work_orders_dispatch') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menyetujui pengeluaran material.');
        }

        try {
            InfrastructureWorkOrderService::approveMaterialDispatch($workOrder, Auth::user());
            return back()->with('success', "Material untuk {$workOrder->wo_number} telah disetujui & stok gudang telah dialokasikan!");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Teknisi: Mulai Pengerjaan Lapangan.
     */
    public function startWork(InfrastructureWorkOrder $workOrder)
    {
        if (!Auth::user()->can('infrastructure_work_orders_start') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk memulai pengerjaan lapangan.');
        }

        $workOrder->update(['status' => 'in_progress']);
        return back()->with('success', "Status pengerjaan diubah menjadi: Sedang Dikerjakan.");
    }

    /**
     * Teknisi: Submit Laporan Pengerjaan, Meteran Kabel, QC Redaman, & Bukti Foto.
     */
    public function submitReport(Request $request, InfrastructureWorkOrder $workOrder)
    {
        if (!Auth::user()->can('infrastructure_work_orders_report') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengirim laporan pengerjaan lapangan.');
        }

        $validated = $request->validate([
            'actual_latitude' => 'nullable|numeric|between:-90,90',
            'actual_longitude' => 'nullable|numeric|between:-180,180',
            'start_point' => 'nullable|string|max:255',
            'end_point' => 'nullable|string|max:255',
            'cable_meter_start' => 'nullable|integer|min:0',
            'cable_meter_end' => 'nullable|integer|min:0',
            'cable_length_used' => 'nullable|integer|min:0',
            'photo_odp' => 'nullable|image|max:10240',
            'photo_pole' => 'nullable|image|max:5120',
            'photo_splicing' => 'nullable|image|max:5120',
            'photo_opm' => 'nullable|image|max:5120',
            'notes' => 'nullable|string|max:1000',
            'optical_checks' => 'nullable|array',
            'optical_checks.*.port_number' => 'required|integer|min:1',
            'optical_checks.*.rx_power_dbm' => 'required|numeric',
            'optical_checks.*.notes' => 'nullable|string|max:100',
            'reconciliations' => 'nullable|array',
            'reconciliations.*.id' => 'required|integer',
            'reconciliations.*.used_qty' => 'required|numeric|min:0',
        ]);

        $photos = [];
        if ($request->hasFile('photo_odp')) {
            $photos['photo_odp'] = $request->file('photo_odp');
        }
        if ($request->hasFile('photo_pole')) {
            $photos['photo_pole'] = $request->file('photo_pole');
        }
        if ($request->hasFile('photo_splicing')) {
            $photos['photo_splicing'] = $request->file('photo_splicing');
        }
        if ($request->hasFile('photo_opm')) {
            $photos['photo_opm'] = $request->file('photo_opm');
        }

        try {
            InfrastructureWorkOrderService::submitFieldReport(
                $workOrder,
                $validated,
                $photos,
                $validated['optical_checks'] ?? [],
                $validated['reconciliations'] ?? [],
                Auth::user()
            );

            $deviceType = $workOrder->type === 'odc_deployment' ? 'ODC' : 'ODP';
            return back()->with('success', "Pengerjaan {$workOrder->wo_number} selesai! Perangkat {$workOrder->target_name} telah otomatis terdaftar & aktif di master data {$deviceType} serta peta GIS.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * NOC / Ka. Teknisi: Setujui Pengerjaan & Komisioning Otomatis ke Master Database & GIS.
     */
    public function approve(InfrastructureWorkOrder $workOrder)
    {
        if (!Auth::user()->can('infrastructure_work_orders_approve') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menyetujui komisioning ke GIS.');
        }

        try {
            InfrastructureWorkOrderService::approveAndCommission($workOrder, Auth::user());
            return back()->with('success', "Pengerjaan {$workOrder->wo_number} berhasil disetujui! Perangkat {$workOrder->target_name} telah aktif di master data & peta GIS.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * NOC / Ka. Teknisi: Tolak / Minta Revisi.
     */
    public function reject(Request $request, InfrastructureWorkOrder $workOrder)
    {
        if (!Auth::user()->can('infrastructure_work_orders_reject') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk meminta revisi pengerjaan.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        InfrastructureWorkOrderService::rejectWorkOrder($workOrder, $validated['reason'], Auth::user());

        return back()->with('success', "Penugasan {$workOrder->wo_number} telah dikembalikan untuk revisi teknisi.");
    }

    /**
     * Teknisi Lapangan: Ajukan Permintaan Tambahan Material (Single / Multi Items).
     */
    public function requestMaterial(Request $request, InfrastructureWorkOrder $workOrder)
    {
        if (!Auth::user()->can('infrastructure_work_orders_request_material') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengajukan permintaan tambahan material.');
        }

        if ($request->has('items') && is_array($request->items)) {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.item_name' => 'required|string|max:255',
                'items.*.material_id' => 'nullable|exists:materials,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit' => 'required|string|max:50',
                'notes' => 'required|string|max:500',
            ]);

            $createdNames = [];
            foreach ($validated['items'] as $item) {
                $itemName = trim($item['item_name'] ?? '');
                $materialId = $item['material_id'] ?? null;
                if ($materialId && !$itemName) {
                    $mat = Material::find($materialId);
                    $itemName = $mat?->name;
                }
                if (!$itemName) continue;

                InfrastructureWorkOrderMaterialRequest::create([
                    'work_order_id' => $workOrder->id,
                    'item_name' => $itemName,
                    'material_id' => $materialId,
                    'requested_by' => Auth::id(),
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'notes' => $validated['notes'],
                    'status' => 'pending',
                ]);

                $createdNames[] = $item['quantity'] . ' ' . $item['unit'] . ' (' . $itemName . ')';
            }

            return redirect()->back()->with('success', count($createdNames) . ' item permintaan tambahan material berhasil diajukan.');
        }

        $validated = $request->validate([
            'item_name' => 'required_without:material_id|nullable|string|max:255',
            'material_id' => 'nullable|exists:materials,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'notes' => 'required|string|max:500',
        ]);

        $materialId = $validated['material_id'] ?? null;
        $itemName = $validated['item_name'] ?? null;
        if ($materialId && !$itemName) {
            $mat = Material::find($materialId);
            $itemName = $mat?->name;
        }

        InfrastructureWorkOrderMaterialRequest::create([
            'work_order_id' => $workOrder->id,
            'item_name' => trim($itemName ?: 'Material Tambahan'),
            'material_id' => $materialId,
            'requested_by' => Auth::id(),
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'],
            'notes' => $validated['notes'],
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Permintaan tambahan material berhasil diajukan ke Gudang / Ka. Teknisi.');
    }

    /**
     * Ka. Teknisi / Gudang / Admin: Setujui Permintaan Tambahan Material.
     */
    public function approveMaterialRequest(Request $request, InfrastructureWorkOrder $workOrder, InfrastructureWorkOrderMaterialRequest $materialRequest)
    {
        if (!Auth::user()->can('infrastructure_work_orders_review_material') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menyetujui permintaan tambahan material.');
        }

        if ($materialRequest->status !== 'pending') {
            return redirect()->back()->with('error', 'Permintaan material ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'material_id' => 'required|exists:materials,id',
            'quantity' => 'nullable|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($workOrder, $materialRequest, $validated) {
            $material = Material::findOrFail($validated['material_id']);
            $qty = !empty($validated['quantity']) ? (float) $validated['quantity'] : (float) $materialRequest->quantity;
            $areaId = $workOrder->area_id;

            $matStock = null;
            if ($areaId) {
                $matStock = MaterialStock::where('material_id', $material->id)
                    ->where('area_id', $areaId)
                    ->first();
            }

            $stockBefore = $matStock ? (float) $matStock->stock : (float) $material->stock;
            $stockAfter = max(0, $stockBefore - $qty);

            // 1. Catat Transaksi Pengeluaran Barang Outbound
            $outTrans = MaterialTransaction::create([
                'transaction_number' => 'OUT-WO-REQ-' . date('YmdHis') . rand(10, 99),
                'type' => 'out',
                'status' => 'approved',
                'date' => now()->toDateString(),
                'technician_name' => $materialRequest->requester?->name ?: Auth::user()->name,
                'purpose' => "Tambahan Material Rollout {$workOrder->target_name} ({$workOrder->wo_number})",
                'source_type' => 'rollout_odc_odp',
                'source_id' => $workOrder->id,
                'source_reference' => $workOrder->wo_number,
                'area_id' => $areaId,
                'user_id' => Auth::id(),
                'notes' => "Disetujui Ka. Teknisi: {$material->name} ({$qty} {$material->unit}). Kebutuhan teknisi: {$materialRequest->item_name} - {$materialRequest->notes}",
                'total_cost' => 0,
            ]);

            $unitPrice = (float) ($material->price_per_unit ?? 0);
            $cost = $qty * $unitPrice;

            MaterialTransactionItem::create([
                'material_transaction_id' => $outTrans->id,
                'material_id' => $material->id,
                'quantity' => $qty,
                'unit' => $material->unit ?? 'pcs',
                'price_per_unit' => $unitPrice,
                'total_price' => $cost,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'condition' => 'Layak Pakai',
            ]);

            $outTrans->update(['total_cost' => $cost]);

            // 2. Potong stok area & master
            if ($matStock) {
                $matStock->decrement('stock', $qty);
            }
            $material->decrement('stock', $qty);

            // 3. Tambahkan ke daftar material Work Order (taken) agar langsung muncul di rekonsiliasi teknisi
            $existingWoMat = InfrastructureWorkOrderMaterial::where('work_order_id', $workOrder->id)
                ->where('material_id', $material->id)
                ->first();

            if ($existingWoMat) {
                $existingWoMat->increment('requested_qty', $qty);
                $existingWoMat->increment('used_qty', $qty);
                $existingWoMat->update([
                    'status' => 'taken',
                    'notes' => ($existingWoMat->notes ? $existingWoMat->notes . '; ' : '') . "Tambahan {$qty} {$material->unit} disetujui",
                ]);
            } else {
                InfrastructureWorkOrderMaterial::create([
                    'work_order_id' => $workOrder->id,
                    'material_id' => $material->id,
                    'requested_qty' => $qty,
                    'used_qty' => $qty,
                    'returned_qty' => 0,
                    'unit' => $material->unit ?? 'pcs',
                    'status' => 'taken',
                    'notes' => "Material tambahan disetujui (Permintaan: {$materialRequest->item_name})",
                ]);
            }

            // 4. Update status permintaan material
            $materialRequest->update([
                'status' => 'approved',
                'material_id' => $material->id,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        });

        return redirect()->back()->with('success', 'Permintaan tambahan material berhasil disetujui dan stok telah dialokasikan.');
    }

    /**
     * Ka. Teknisi / Gudang / Admin: Tolak Permintaan Tambahan Material.
     */
    public function rejectMaterialRequest(Request $request, InfrastructureWorkOrder $workOrder, InfrastructureWorkOrderMaterialRequest $materialRequest)
    {
        if (!Auth::user()->can('infrastructure_work_orders_review_material') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menolak permintaan tambahan material.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $materialRequest->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['reason'],
        ]);

        return redirect()->back()->with('success', 'Permintaan tambahan material telah ditolak.');
    }

    /**
     * Upload / Perbarui Foto Fisik ODP / ODC Langsung dan Sinkronkan ke Master Data GIS.
     */
    public function uploadPhoto(Request $request, InfrastructureWorkOrder $workOrder)
    {
        if (!Auth::user()->can('infrastructure_work_orders_upload_photo') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengunggah foto perangkat.');
        }

        $request->validate([
            'photo_odp' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $dir = $workOrder->type === 'odc_deployment' ? 'infrastructure/odc' : 'infrastructure/odp';
        $path = $request->file('photo_odp')->store($dir, 'public');

        $workOrder->update(['photo_odp' => $path]);
        InfrastructureWorkOrderService::syncPhotoToMaster($workOrder, $path);

        return back()->with('success', 'Foto perangkat ODC/ODP berhasil diperbarui dan otomatis disinkronkan ke master data & GIS!');
    }

    /**
     * Hapus / Batalkan Work Order Rollout & Kembalikan seluruh material ke Gudang.
     */
    public function destroy(InfrastructureWorkOrder $workOrder)
    {
        if (!Auth::user()->can('infrastructure_work_orders_delete') && !Auth::user()->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membatalkan/menghapus Work Order.');
        }

        $woNum = $workOrder->wo_number;
        $target = $workOrder->target_name;

        DB::transaction(function () use ($workOrder) {
            // 1. Kembalikan seluruh material pengeluaran (OUT) ke stok gudang & bersihkan transaksi
            try {
                \App\Services\MaterialHandoverService::restoreDispatchedMaterials(
                    'rollout_odc_odp',
                    $workOrder->id,
                    $workOrder->area_id,
                    $workOrder->wo_number
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[InfrastructureWorkOrderController::destroy] Gagal restore material handover: ' . $e->getMessage());
            }

            // 2. Fallback: Kembalikan dari tabel infrastructure_work_order_materials jika status taken
            foreach ($workOrder->materials as $woMat) {
                $qty = (float) ($woMat->used_qty ?: $woMat->requested_qty);
                if ($woMat->status === 'taken' && $qty > 0 && $woMat->material_id) {
                    $material = Material::find($woMat->material_id);
                    if ($material) {
                        $material->increment('stock', $qty);
                        if (str_contains(strtolower($material->category ?? ''), 'kabel') && $material->meter_per_roll > 0) {
                            $material->total_rolls = $material->stock / $material->meter_per_roll;
                            $material->save();
                        }
                    }
                    if ($workOrder->area_id) {
                        $matStock = \App\Models\MaterialStock::where('material_id', $woMat->material_id)
                            ->where('area_id', $workOrder->area_id)
                            ->first();
                        if ($matStock) {
                            $matStock->increment('stock', $qty);
                            if ($material && $material->meter_per_roll > 0) {
                                $matStock->total_rolls = $matStock->stock / $material->meter_per_roll;
                                $matStock->save();
                            }
                        }
                    }
                }
            }

            // 3. Hapus foto dari disk storage jika ada
            $photos = [$workOrder->photo_pole, $workOrder->photo_splicing, $workOrder->photo_opm, $workOrder->photo_odp];
            foreach ($photos as $photo) {
                if ($photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($photo)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($photo);
                }
            }

            $workOrder->opticalChecks()->delete();
            $workOrder->materials()->delete();
            $workOrder->materialRequests()->delete();
            $workOrder->technicians()->detach();
            $workOrder->delete();
        });

        return redirect()->route('infrastructure.work-orders.index')->with('success', "Work Order {$woNum} ({$target}) berhasil dibatalkan/dihapus dan seluruh material telah dikembalikan ke stok gudang.");
    }
}
