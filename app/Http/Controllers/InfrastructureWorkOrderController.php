<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\InfrastructureWorkOrder;
use App\Models\Material;
use App\Models\Odc;
use App\Models\Olt;
use App\Models\User;
use App\Services\InfrastructureWorkOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class InfrastructureWorkOrderController extends Controller
{
    /**
     * Dashboard & Daftar Penugasan Pembangunan ODC & ODP.
     */
    public function index(Request $request): Response
    {
        $query = InfrastructureWorkOrder::with(['area', 'olt', 'parentOdc', 'technician', 'creator']);

        // Filter Hak Akses Area
        if (Auth::check() && !Auth::user()->hasRole('admin') && !Auth::user()->can('network_odc_view_all')) {
            if (Auth::user()->can('network_odc_view_area')) {
                $query->whereIn('area_id', Auth::user()->getAccessibleAreaIds());
            } elseif (Auth::user()->role === 'teknisi') {
                $query->where('technician_id', Auth::id());
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

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->area_id) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->technician_id) {
            $query->where('technician_id', $request->technician_id);
        }

        $workOrders = $query->latest('id')->paginate(15)->withQueryString();

        // KPI Ringkasan Status
        $kpiQuery = InfrastructureWorkOrder::query();
        if (Auth::check() && !Auth::user()->hasRole('admin') && Auth::user()->role === 'teknisi') {
            $kpiQuery->where('technician_id', Auth::id());
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
            'areas' => Area::orderBy('name')->get(['id', 'name']),
            'technicians' => User::whereIn('role', ['teknisi', 'admin', 'noc'])->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'type', 'status', 'area_id', 'technician_id']),
        ]);
    }

    /**
     * Form Pembuatan Penugasan Baru (Smart Wizard).
     */
    public function create(): Response
    {
        return Inertia::render('Infrastructure/WorkOrders/Create', [
            'areas' => Area::orderBy('name')->get(['id', 'name']),
            'olts' => Olt::where('status', 'active')->get(['id', 'name', 'area_id', 'total_pon_ports']),
            'odcs' => Odc::where('status', 'active')->get(['id', 'name', 'area_id', 'capacity', 'olt_id', 'pon_port']),
            'technicians' => User::whereIn('role', ['teknisi', 'admin', 'noc'])->orderBy('name')->get(['id', 'name']),
            'materials' => Material::orderBy('name')->get(['id', 'name', 'unit', 'stock', 'price_per_unit']),
        ]);
    }

    /**
     * API: Ambil Rekomendasi BOM (Bill of Materials) Otomatis.
     */
    public function getBOM(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:odc_deployment,odp_deployment',
            'capacity' => 'required|integer|min:1',
            'cable_length' => 'nullable|integer|min:0',
            'area_id' => 'nullable|exists:areas,id',
        ]);

        $bom = InfrastructureWorkOrderService::getSuggestedBOM(
            $validated['type'],
            (int) $validated['capacity'],
            (int) ($validated['cable_length'] ?? 150),
            $validated['area_id'] ?? null
        );

        return response()->json($bom);
    }

    /**
     * Simpan Work Order Baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:odc_deployment,odp_deployment',
            'target_name' => 'required|string|max:255',
            'area_id' => 'required|exists:areas,id',
            'olt_id' => 'nullable|required_if:type,odc_deployment|exists:olts,id',
            'pon_port' => 'nullable|integer|min:1',
            'odc_id' => 'nullable|required_if:type,odp_deployment|exists:odcs,id',
            'odc_splitter_port' => 'nullable|integer|min:1',
            'capacity' => 'required|integer|min:1',
            'splitter_ratio' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:500',
            'planned_latitude' => 'nullable|numeric|between:-90,90',
            'planned_longitude' => 'nullable|numeric|between:-180,180',
            'technician_id' => 'required|exists:users,id',
            'scheduled_date' => 'required|date',
            'start_point' => 'nullable|string|max:255',
            'end_point' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'materials' => 'required|array|min:1',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.quantity' => 'required|numeric|min:0.01',
            'materials.*.unit' => 'nullable|string|max:20',
        ]);

        $workOrder = InfrastructureWorkOrderService::createWorkOrder(
            $validated,
            $validated['materials'],
            Auth::user()
        );

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
            'creator',
            'approver',
            'materials.material',
            'opticalChecks',
            'createdOdc',
            'createdOdp',
        ]);

        return Inertia::render('Infrastructure/WorkOrders/Show', [
            'workOrder' => $workOrder,
        ]);
    }

    /**
     * Tampilan Cetak Surat Jalan Pengeluaran Material.
     */
    public function printSuratJalan(InfrastructureWorkOrder $workOrder)
    {
        $workOrder->load(['area', 'olt', 'parentOdc', 'technician', 'creator', 'materials.material']);

        return Inertia::render('Infrastructure/WorkOrders/PrintSuratJalan', [
            'workOrder' => $workOrder,
        ]);
    }

    /**
     * Gudang: Konfirmasi Pengeluaran Material & Cetak Surat Jalan.
     */
    public function approveMaterial(InfrastructureWorkOrder $workOrder)
    {
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
        $workOrder->update(['status' => 'in_progress']);
        return back()->with('success', "Status pengerjaan diubah menjadi: Sedang Dikerjakan.");
    }

    /**
     * Teknisi: Submit Laporan Pengerjaan, Meteran Kabel, QC Redaman, & Bukti Foto.
     */
    public function submitReport(Request $request, InfrastructureWorkOrder $workOrder)
    {
        $validated = $request->validate([
            'actual_latitude' => 'nullable|numeric|between:-90,90',
            'actual_longitude' => 'nullable|numeric|between:-180,180',
            'start_point' => 'nullable|string|max:255',
            'end_point' => 'nullable|string|max:255',
            'cable_meter_start' => 'nullable|integer|min:0',
            'cable_meter_end' => 'nullable|integer|min:0',
            'cable_length_used' => 'nullable|integer|min:0',
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
                $validated['reconciliations'] ?? []
            );

            return back()->with('success', "Laporan pengerjaan {$workOrder->wo_number} berhasil dikirim! Menunggu verifikasi & komisioning NOC.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * NOC: Setujui Pengerjaan & Komisioning Otomatis ke Master Database & GIS.
     */
    public function approve(InfrastructureWorkOrder $workOrder)
    {
        try {
            InfrastructureWorkOrderService::approveAndCommission($workOrder, Auth::user());
            return back()->with('success', "Pengerjaan {$workOrder->wo_number} berhasil disetujui! Perangkat {$workOrder->target_name} telah aktif di master data & peta GIS.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * NOC: Tolak / Minta Revisi.
     */
    public function reject(Request $request, InfrastructureWorkOrder $workOrder)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        InfrastructureWorkOrderService::rejectWorkOrder($workOrder, $validated['reason'], Auth::user());

        return back()->with('success', "Penugasan {$workOrder->wo_number} telah dikembalikan untuk revisi teknisi.");
    }
}
