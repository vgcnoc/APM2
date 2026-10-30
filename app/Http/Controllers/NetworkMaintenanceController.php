<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NetworkMaintenance;
use App\Models\NetworkMaintenanceMaterial;
use App\Models\Area;
use App\Models\Odp;
use App\Models\Odc;
use App\Models\User;
use App\Models\Employee;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Services\ImageCompressionService;

class NetworkMaintenanceController extends Controller
{
    /**
     * Halaman Utama Khusus Pemeliharaan Jalur (ODP, ODC, Jalur Utama)
     * Didesain khusus & bersih untuk teknisi & maintenance lapangan
     */
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $areaId = $request->input('area_id');
        $status = $request->input('status');
        $category = $request->input('category');
        $search = $request->input('search');
        $myTasksOnly = $request->boolean('my_tasks');

        $user = auth()->user();
        $isGlobal = $user && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->role === 'admin' || $user->can('operational_progress_view_all'));
        $accessibleAreaIds = (!$isGlobal && $user) ? $user->getAccessibleAreaIds() : null;

        $targetAreaIds = null;
        if ($accessibleAreaIds !== null) {
            // User dibatasi per area kerja mereka (karyawan hanya bisa lihat data per area)
            if (empty($accessibleAreaIds)) {
                $targetAreaIds = [-1]; // Jika belum di-assign area, blokir data area lain
                $areaId = null;
            } else {
                if ($areaId) {
                    $requestedAreaId = (int) $areaId;
                    if (in_array($requestedAreaId, $accessibleAreaIds, true)) {
                        $targetAreaIds = [$requestedAreaId];
                    } else {
                        $targetAreaIds = $accessibleAreaIds;
                        $areaId = $accessibleAreaIds[0];
                    }
                } else {
                    $targetAreaIds = $accessibleAreaIds;
                    $areaId = $accessibleAreaIds[0];
                }
            }
        } else {
            // User global / admin: bebas filter area manapun atau tampilkan semua
            if ($areaId) {
                $targetAreaIds = [(int) $areaId];
            }
        }

        $areas = \App\Services\DataScopeService::getScopedAreas($user, 'operational_progress');

        // Query data pemeliharaan
        $query = NetworkMaintenance::with(['area', 'odp', 'odc', 'technicians', 'creator', 'materials.material']);

        // Filter Tanggal
        $query->whereDate('scheduled_date', $date);

        // Filter Wilayah
        if (!empty($targetAreaIds)) {
            $query->where(function ($q) use ($targetAreaIds) {
                $q->whereIn('area_id', $targetAreaIds)
                  ->orWhereHas('odp', fn($oq) => $oq->whereIn('area_id', $targetAreaIds))
                  ->orWhereHas('odc', fn($cq) => $cq->whereIn('area_id', $targetAreaIds));
            });
        }

        // Filter Status
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        // Filter Kategori
        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        // Filter Pencarian
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('maintenance_number', 'like', "%{$search}%")
                  ->orWhere('location_name', 'like', "%{$search}%")
                  ->orWhereHas('odp', fn($oq) => $oq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('odc', fn($cq) => $cq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('technicians', fn($tq) => $tq->where('name', 'like', "%{$search}%"));
            });
        }

        $allItems = $query->orderBy('scheduled_time')->orderBy('id', 'desc')->get();

        $maintenanceList = [];
        $userId = $user ? $user->id : 0;
        $userName = $user ? strtolower($user->name) : '';

        foreach ($allItems as $m) {
            $techNames = $m->technicians->pluck('name')->toArray();
            $techniciansStr = !empty($techNames) ? implode(', ', array_unique($techNames)) : 'Tim Maintenance Jalur';
            $techIds = $m->technicians->pluck('id')->toArray();

            $isDone = ($m->status === 'completed') || !empty($m->completed_at);
            $isInProgress = ($m->status === 'in_progress') || (!empty($m->start_time) && empty($m->end_time) && !$isDone);
            $isRescheduled = ($m->status === 'rescheduled');

            $duration = '-';
            if ($m->start_time && $m->end_time) {
                $duration = substr($m->start_time, 0, 5) . ' - ' . substr($m->end_time, 0, 5);
            } elseif ($m->start_time) {
                $duration = 'Mulai ' . substr($m->start_time, 0, 5);
            } elseif ($m->scheduled_time) {
                $duration = 'Jadwal ' . substr((string)$m->scheduled_time, 0, 5);
            }

            $location = $m->location_name ?: ($m->odp?->name ?: ($m->odc?->name ?: ($m->area?->name ?? 'Wilayah Lapangan')));

            $isMyTask = in_array($userId, $techIds) || (str_contains(strtolower($techniciansStr), $userName));

            $maintenanceList[] = [
                'id' => $m->id,
                'maintenance_number' => $m->maintenance_number,
                'category' => $m->category,
                'category_label' => $m->category_label,
                'category_icon' => $m->category_icon,
                'title' => $m->title,
                'location' => $location,
                'area_name' => $m->area?->name ?? '-',
                'odp' => $m->odp?->name ?? '-',
                'odc' => $m->odc?->name ?? '-',
                'technicians' => $techniciansStr,
                'technician_ids' => $techIds,
                'duration' => $duration,
                'start_time' => $m->start_time,
                'end_time' => $m->end_time,
                'scheduled_time' => $m->scheduled_time,
                'scheduled_date' => $m->scheduled_date ? Carbon::parse($m->scheduled_date)->toDateString() : null,
                'status' => $m->status,
                'is_done' => $isDone,
                'is_in_progress' => $isInProgress,
                'is_rescheduled' => $isRescheduled,
                'priority' => $m->priority,
                'description' => $m->description,
                'action_taken' => $m->action_taken,
                'optical_power_before' => $m->optical_power_before,
                'optical_power_after' => $m->optical_power_after,
                'photo_before' => $m->photo_before ? Storage::url($m->photo_before) : null,
                'photo_after' => $m->photo_after_urls[0] ?? ($m->photo_after ? Storage::url($m->photo_after) : null),
                'photo_after_urls' => $m->photo_after_urls,
                'photo_before_urls' => $m->photo_before_urls,
                'created_by' => $m->created_by,
                'is_my_task' => $isMyTask,
                'materials' => $m->materials->map(fn($mat) => [
                    'id' => $mat->id,
                    'material_id' => $mat->material_id,
                    'name' => $mat->material?->name ?? 'Material',
                    'material_name' => $mat->material?->name ?? 'Material',
                    'category' => $mat->material?->category ?? '',
                    'material_transaction_item_id' => $mat->material_transaction_item_id,
                    'trx_number' => $mat->trx_number,
                    'quantity_prepared' => (float) $mat->quantity_prepared,
                    'quantity_used' => (float) $mat->quantity_used,
                    'quantity_returned' => (float) $mat->quantity_returned,
                    'unit' => $mat->unit ?: ($mat->material?->unit ?? 'pcs'),
                    'notes' => $mat->notes,
                ])->values(),
            ];
        }

        // Summary counts
        $summary = [
            'total' => count($maintenanceList),
            'done' => collect($maintenanceList)->where('is_done', true)->count(),
            'in_progress' => collect($maintenanceList)->where('is_in_progress', true)->count(),
            'rescheduled' => collect($maintenanceList)->where('is_rescheduled', true)->count(),
            'pending' => collect($maintenanceList)->where('is_done', false)->where('is_in_progress', false)->where('is_rescheduled', false)->count(),
            'my_tasks' => collect($maintenanceList)->where('is_my_task', true)->count(),
        ];

        // Daftar teknisi dan maintenance lapangan untuk form create
        $rawTechnicians = User::where(function ($q) {
            $q->where('is_active', true)->orWhereNull('is_active');
        })->where(function ($q) {
            $q->whereHas('roles', function ($r) {
                $r->where(function ($rq) {
                    $rq->where('name', 'LIKE', '%teknisi%')
                       ->orWhere('name', 'LIKE', '%maintenance%')
                       ->orWhere('name', 'LIKE', '%lapangan%')
                       ->orWhere('name', 'LIKE', '%jaringan%')
                       ->orWhere('name', 'LIKE', '%serbu%');
                });
            })->orWhere(function ($uq) {
                $uq->where('role', 'LIKE', '%teknisi%')
                   ->orWhere('role', 'LIKE', '%maintenance%')
                   ->orWhere('role', 'LIKE', '%lapangan%')
                   ->orWhere('role', 'LIKE', '%jaringan%')
                   ->orWhere('role', 'LIKE', '%serbu%');
            });
        })->with('roles')->orderBy('name')->get(['id', 'name', 'phone', 'area_id', 'role']);

        $employees = Employee::select('id', 'name', 'position')->get();
        $empMapByName = [];
        foreach ($employees as $e) {
            $key = mb_strtolower(trim((string)$e->name));
            if ($key !== '' && !isset($empMapByName[$key])) {
                $empMapByName[$key] = $e;
            }
        }

        $availableTechnicians = $rawTechnicians->map(function ($u) use ($empMapByName) {
            $nm = mb_strtolower(trim((string)$u->name));
            $emp = $empMapByName[$nm] ?? null;
            $pos = mb_strtolower(trim((string)($emp?->position ?? '')));
            $roleCol = mb_strtolower(trim((string)($u->role ?? '')));
            $isMaint = str_contains($pos, 'maintenance') || str_contains($roleCol, 'maintenance');
            if (!$isMaint && $u->relationLoaded('roles')) {
                foreach ($u->roles as $r) {
                    if (str_contains(mb_strtolower((string)$r->name), 'maintenance')) {
                        $isMaint = true;
                        break;
                    }
                }
            }
            return [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone,
                'area_id' => $u->area_id,
                'is_maintenance' => $isMaint,
                'division' => $isMaint ? 'maintenance' : 'teknisi',
                'role_label' => $isMaint ? 'Maintenance' : 'Teknisi',
            ];
        });

        $availableOdps = Odp::select('id', 'name', 'area_id')->orderBy('name')->limit(500)->get();
        $availableOdcs = Odc::select('id', 'name', 'area_id')->orderBy('name')->limit(150)->get();

        $carbonDate = Carbon::parse($date)->locale('id');
        $indonesianDate = $carbonDate->isoFormat('dddd, D MMMM Y');

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

        return Inertia::render('Operational/NetworkMaintenance', [
            'maintenanceList' => $maintenanceList,
            'summary' => $summary,
            'areas' => $areas,
            'materials' => $materials,
            'materialTransactions' => $materialTransactions,
            'filters' => [
                'date' => $date,
                'area_id' => $areaId ? (int) $areaId : null,
                'status' => $status ?: 'all',
                'category' => $category ?: 'all',
                'search' => $search ?: '',
                'my_tasks' => $myTasksOnly,
            ],
            'availableTechnicians' => $availableTechnicians,
            'availableOdps' => $availableOdps,
            'availableOdcs' => $availableOdcs,
            'indonesianDate' => $indonesianDate,
            'canCreate' => $user ? ($user->can('network_maintenance_create') || $user->hasRole('admin') || $user->hasRole('super admin') || $user->hasRole('kepala teknisi')) : true,
            'canDelete' => $user ? ($user->can('network_maintenance_delete') || $user->hasRole('admin') || $user->hasRole('super admin') || $user->hasRole('kepala teknisi')) : false,
            'canViewAllAreas' => $isGlobal,
            'isAreaScoped' => !$isGlobal,
        ]);
    }

    /**
     * Simpan Kegiatan Pemeliharaan & Perapihan Jalur Baru
     */
    public function store(Request $request)
    {
        if ($request->has('materials')) {
            $mats = $request->input('materials');
            if (is_string($mats)) {
                $mats = json_decode($mats, true);
            }
            if (is_array($mats)) {
                $request->merge(['materials' => $mats]);
            }
        }

        $validated = $request->validate([
            'category' => 'required|string|in:perapihan_jalur,pengecekan_odp,pengecekan_odc,pengecekan_jalur_utama,pemeliharaan_rutin',
            'title' => 'required|string|max:255',
            'area_id' => 'nullable|exists:areas,id',
            'odp_id' => 'nullable|exists:odps,id',
            'odc_id' => 'nullable|exists:odcs,id',
            'location_name' => 'nullable|string|max:255',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|string|max:10',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'status' => 'nullable|string|in:scheduled,in_progress,completed,rescheduled',
            'description' => 'nullable|string',
            'action_taken' => 'nullable|string',
            'optical_power_before' => 'nullable|string|max:50',
            'optical_power_after' => 'nullable|string|max:50',
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
            'photo_before' => 'nullable|image|max:25600',
            'photo_after' => 'nullable|image|max:25600',
            'materials' => 'nullable|array',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.material_transaction_item_id' => 'nullable',
            'materials.*.trx_number' => 'nullable|string|max:100',
            'materials.*.quantity_prepared' => 'nullable|numeric|min:0.01',
            'materials.*.quantity' => 'nullable|numeric|min:0.01',
            'materials.*.unit' => 'nullable|string',
            'materials.*.notes' => 'nullable|string',
        ], [
            'category.required' => 'Jenis kegiatan wajib dipilih.',
            'title.required' => 'Judul kegiatan wajib diisi.',
            'scheduled_date.required' => 'Tanggal pelaksanaan wajib diisi.',
        ]);

        try {
            DB::beginTransaction();

            $photoBeforePath = null;
            if ($request->hasFile('photo_before')) {
                $photoBeforePath = ImageCompressionService::compressAndStore($request->file('photo_before'), 'maintenance_photos', 'public');
            }

            $photoAfterPath = null;
            if ($request->hasFile('photo_after')) {
                $photoAfterPath = ImageCompressionService::compressAndStore($request->file('photo_after'), 'maintenance_photos', 'public');
            }

            $status = $validated['status'] ?? 'scheduled';
            $now = Carbon::now();

            $startTime = null;
            $endTime = null;
            $completedBy = null;
            $completedAt = null;

            if ($status === 'in_progress') {
                $startTime = $now->format('H:i');
            } elseif ($status === 'completed') {
                $startTime = $startTime ?: $now->format('H:i');
                $endTime = $now->format('H:i');
                $completedBy = auth()->id();
                $completedAt = $now;
            }

            $maintenance = NetworkMaintenance::create([
                'maintenance_number' => NetworkMaintenance::generateMaintenanceNumber(),
                'category' => $validated['category'],
                'title' => $validated['title'],
                'area_id' => $validated['area_id'] ?? null,
                'odp_id' => $validated['odp_id'] ?? null,
                'odc_id' => $validated['odc_id'] ?? null,
                'location_name' => $validated['location_name'] ?? null,
                'priority' => $validated['priority'] ?? 'normal',
                'status' => $status,
                'scheduled_date' => $validated['scheduled_date'],
                'scheduled_time' => $validated['scheduled_time'] ?? null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'description' => $validated['description'] ?? null,
                'action_taken' => $validated['action_taken'] ?? null,
                'optical_power_before' => $validated['optical_power_before'] ?? null,
                'optical_power_after' => $validated['optical_power_after'] ?? null,
                'photo_before' => $photoBeforePath,
                'photo_after' => $photoAfterPath,
                'created_by' => auth()->id(),
                'completed_by' => $completedBy,
                'completed_at' => $completedAt,
            ]);

            // Hubungkan teknisi pelaksana
            if (!empty($validated['technician_ids'])) {
                $maintenance->technicians()->sync($validated['technician_ids']);
            }

            // Penyiapan Material dari Gudang / Inventory untuk Petugas Lapangan
            if (!empty($validated['materials'])) {
                $techNames = !empty($validated['technician_ids'])
                    ? User::whereIn('id', $validated['technician_ids'])->pluck('name')->join(', ')
                    : auth()->user()->name;

                $sourceOrders = array_filter(array_unique(array_column($validated['materials'], 'trx_number')));
                $orderRefStr = !empty($sourceOrders) ? ' (Ref: ' . implode(', ', $sourceOrders) . ')' : '';

                $matTrx = MaterialTransaction::create([
                    'transaction_number' => 'OUT-MNT-' . date('YmdHis') . rand(10, 99),
                    'type' => 'out',
                    'status' => 'approved',
                    'date' => now(),
                    'technician_name' => $techNames ?: auth()->user()->name,
                    'purpose' => 'Persiapan Material Pemeliharaan: ' . $maintenance->maintenance_number . ' (' . $maintenance->title . ')' . $orderRefStr,
                    'source_type' => 'network_maintenance',
                    'source_id' => $maintenance->id,
                    'source_reference' => $maintenance->maintenance_number,
                    'user_id' => auth()->id(),
                    'area_id' => $maintenance->area_id,
                ]);

                foreach ($validated['materials'] as $matItem) {
                    if (empty($matItem['material_id'])) continue;
                    $qty = (float) ($matItem['quantity_prepared'] ?? ($matItem['quantity'] ?? 0));
                    if ($qty <= 0) continue;

                    $material = Material::find($matItem['material_id']);
                    if ($material) {
                        $itemUnit = $matItem['unit'] ?? ($material->unit ?? 'pcs');

                        NetworkMaintenanceMaterial::create([
                            'network_maintenance_id' => $maintenance->id,
                            'material_id' => $material->id,
                            'material_transaction_item_id' => $matItem['material_transaction_item_id'] ?? null,
                            'trx_number' => $matItem['trx_number'] ?? null,
                            'quantity_prepared' => $qty,
                            'quantity_used' => 0,
                            'quantity_returned' => 0,
                            'unit' => $itemUnit,
                            'notes' => $matItem['notes'] ?? null,
                        ]);

                        MaterialTransactionItem::create([
                            'material_transaction_id' => $matTrx->id,
                            'material_id' => $material->id,
                            'quantity' => $qty,
                            'unit' => $itemUnit,
                        ]);

                        if ($maintenance->area_id) {
                            $matStock = MaterialStock::where('material_id', $material->id)
                                ->where('area_id', $maintenance->area_id)
                                ->first();
                            if ($matStock) {
                                $matStock->decrement('stock', $qty);
                                if ($material->meter_per_roll > 0) {
                                    $matStock->total_rolls = $matStock->stock / $material->meter_per_roll;
                                    $matStock->save();
                                }
                            }
                        }

                        $material->decrement('stock', $qty);
                        if ($material->meter_per_roll > 0) {
                            $material->total_rolls = $material->stock / $material->meter_per_roll;
                            $material->save();
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kegiatan pemeliharaan/perapihan jalur berhasil ditambahkan!',
                'maintenance' => $maintenance->load(['area', 'odp', 'odc', 'technicians', 'materials.material']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[NetworkMaintenanceController::store] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan kegiatan pemeliharaan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update Cepat Status & Laporan Hasil Kerja (Live Lapangan)
     */
    public function quickUpdate(Request $request, NetworkMaintenance $maintenance)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:scheduled,in_progress,completed,rescheduled,cancelled',
            'action_taken' => 'nullable|string',
            'optical_power_before' => 'nullable|string|max:50',
            'optical_power_after' => 'nullable|string|max:50',
            'scheduled_date' => 'nullable|date',
            'start_time' => 'nullable|string|max:10',
            'end_time' => 'nullable|string|max:10',
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
            'photo_before' => 'nullable|image|max:25600',
            'photo_after' => 'nullable|image|max:25600',
        ]);

        try {
            $updateData = [
                'status' => $validated['status'],
            ];

            if ($request->filled('action_taken')) {
                $updateData['action_taken'] = $validated['action_taken'];
            }
            if ($request->filled('optical_power_before')) {
                $updateData['optical_power_before'] = $validated['optical_power_before'];
            }
            if ($request->filled('optical_power_after')) {
                $updateData['optical_power_after'] = $validated['optical_power_after'];
            }
            if ($request->filled('scheduled_date')) {
                $updateData['scheduled_date'] = $validated['scheduled_date'];
            }

            $now = Carbon::now();

            if ($validated['status'] === 'in_progress') {
                $updateData['start_time'] = $validated['start_time'] ?? ($maintenance->start_time ?: $now->format('H:i'));
            } elseif ($validated['status'] === 'completed') {
                $updateData['start_time'] = $validated['start_time'] ?? ($maintenance->start_time ?: $now->format('H:i'));
                $updateData['end_time'] = $validated['end_time'] ?? $now->format('H:i');
                $updateData['completed_by'] = auth()->id();
                $updateData['completed_at'] = $now;
            } elseif ($validated['status'] === 'scheduled') {
                $updateData['completed_by'] = null;
                $updateData['completed_at'] = null;
            }

            // Upload foto baru jika ada (Mendukung Multi-Foto Lapangan & Auto-Kompresi Cerdas)
            $existingPhotos = [];
            if (!empty($maintenance->photo_after)) {
                $dec = json_decode($maintenance->photo_after, true);
                $existingPhotos = is_array($dec) ? $dec : [$maintenance->photo_after];
            }

            $newUploadedPaths = [];
            if ($request->hasFile('photos_after')) {
                foreach ($request->file('photos_after') as $file) {
                    if ($file && $file->isValid()) {
                        $newUploadedPaths[] = ImageCompressionService::compressAndStore($file, 'maintenance_photos', 'public');
                    }
                }
            }
            if ($request->hasFile('photo_after')) {
                $file = $request->file('photo_after');
                if ($file && $file->isValid()) {
                    $newUploadedPaths[] = ImageCompressionService::compressAndStore($file, 'maintenance_photos', 'public');
                }
            }

            if ($request->has('existing_photos_after') || !empty($newUploadedPaths)) {
                $keptOldPhotos = [];
                if ($request->has('existing_photos_after')) {
                    $keptRaw = $request->input('existing_photos_after');
                    if (is_string($keptRaw)) $keptRaw = json_decode($keptRaw, true);
                    if (is_array($keptRaw)) {
                        foreach ($keptRaw as $url) {
                            foreach ($existingPhotos as $origPath) {
                                if (str_contains((string)$url, (string)$origPath) || str_contains((string)$origPath, basename((string)$url))) {
                                    $keptOldPhotos[] = $origPath;
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    $keptOldPhotos = $existingPhotos;
                }
                $finalPhotos = array_values(array_unique(array_merge($keptOldPhotos, $newUploadedPaths)));
                $updateData['photo_after'] = !empty($finalPhotos) ? json_encode($finalPhotos) : null;
            }

            if ($request->hasFile('photo_before')) {
                if ($maintenance->photo_before && Storage::disk('public')->exists($maintenance->photo_before)) {
                    Storage::disk('public')->delete($maintenance->photo_before);
                }
                $updateData['photo_before'] = ImageCompressionService::compressAndStore($request->file('photo_before'), 'maintenance_photos', 'public');
            }

            $maintenance->update($updateData);

            if (isset($validated['technician_ids'])) {
                $maintenance->technicians()->sync($validated['technician_ids']);
            }

            // Update pemakaian & pengembalian material jika ada
            if ($request->has('materials')) {
                $mats = $request->input('materials');
                if (is_string($mats)) {
                    $mats = json_decode($mats, true);
                }
                if (is_array($mats)) {
                    foreach ($mats as $mItem) {
                        if (empty($mItem['id'])) continue;
                        $matRow = NetworkMaintenanceMaterial::where('network_maintenance_id', $maintenance->id)
                            ->where('id', $mItem['id'])
                            ->first();
                        if ($matRow) {
                            $oldUsed = (float) $matRow->quantity_used;
                            $oldReturned = (float) $matRow->quantity_returned;

                            $newUsed = isset($mItem['quantity_used']) ? (float) $mItem['quantity_used'] : $oldUsed;
                            $newReturned = isset($mItem['quantity_returned']) ? (float) $mItem['quantity_returned'] : $oldReturned;

                            $matRow->update([
                                'quantity_used' => $newUsed,
                                'quantity_returned' => $newReturned,
                                'notes' => $mItem['notes'] ?? $matRow->notes,
                            ]);

                            // Jika ada perubahan selisih barang kembali, sesuaikan stok gudang secara cerdas
                            $returnDiff = $newReturned - $oldReturned;
                            if ($returnDiff != 0) {
                                $material = Material::find($matRow->material_id);
                                if ($material) {
                                    if ($returnDiff > 0) {
                                        $material->increment('stock', $returnDiff);
                                    } else {
                                        $material->decrement('stock', abs($returnDiff));
                                    }
                                    if ($maintenance->area_id) {
                                        $matStock = MaterialStock::where('material_id', $material->id)
                                            ->where('area_id', $maintenance->area_id)
                                            ->first();
                                        if ($matStock) {
                                            if ($returnDiff > 0) {
                                                $matStock->increment('stock', $returnDiff);
                                            } else {
                                                $matStock->decrement('stock', abs($returnDiff));
                                            }
                                        }
                                    }

                                    // Catat riwayat transaksi mutasi pengembalian sisa material
                                    if ($returnDiff > 0) {
                                        try {
                                            $trx = MaterialTransaction::create([
                                                'transaction_number' => MaterialTransaction::generateTransactionNumber(),
                                                'type' => 'in',
                                                'purpose' => "Pengembalian Sisa Material Pemeliharaan ({$maintenance->maintenance_number})",
                                                'area_id' => $maintenance->area_id,
                                                'notes' => "Sisa material dikembalikan dari kegiatan {$maintenance->title}",
                                                'created_by' => auth()->id() ?? 1,
                                            ]);
                                            MaterialTransactionItem::create([
                                                'material_transaction_id' => $trx->id,
                                                'material_id' => $material->id,
                                                'quantity' => $returnDiff,
                                                'unit' => $matRow->unit ?: ($material->unit ?? 'pcs'),
                                            ]);
                                        } catch (\Throwable $te) {
                                            Log::warning('[NetworkMaintenanceController::quickUpdate] Trx Log Warning: ' . $te->getMessage());
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Status pemeliharaan jalur berhasil diperbarui!',
                'maintenance' => $maintenance->fresh(['area', 'odp', 'odc', 'technicians', 'materials.material']),
            ]);
        } catch (\Throwable $e) {
            Log::error('[NetworkMaintenanceController::quickUpdate] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus Kegiatan Pemeliharaan
     */
    public function destroy(NetworkMaintenance $maintenance)
    {
        try {
            DB::beginTransaction();

            // Kembalikan alokasi material ke stok jika belum dipakai
            foreach ($maintenance->materials as $mat) {
                $qtyPrepared = (float) $mat->quantity_prepared;
                $qtyUsed = (float) $mat->quantity_used;
                $qtyReturned = (float) $mat->quantity_returned;
                
                $qtyToRestore = max(0, $qtyPrepared - $qtyUsed - $qtyReturned);
                if ($qtyToRestore > 0) {
                    $material = Material::find($mat->material_id);
                    if ($material) {
                        $material->increment('stock', $qtyToRestore);
                        if ($maintenance->area_id) {
                            $matStock = MaterialStock::where('material_id', $material->id)
                                ->where('area_id', $maintenance->area_id)
                                ->first();
                            if ($matStock) {
                                $matStock->increment('stock', $qtyToRestore);
                            }
                        }
                    }
                }
            }

            if ($maintenance->photo_before && Storage::disk('public')->exists($maintenance->photo_before)) {
                Storage::disk('public')->delete($maintenance->photo_before);
            }
            if ($maintenance->photo_after && Storage::disk('public')->exists($maintenance->photo_after)) {
                Storage::disk('public')->delete($maintenance->photo_after);
            }

            $maintenance->technicians()->detach();
            $maintenance->materials()->delete();
            $maintenance->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kegiatan pemeliharaan jalur berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[NetworkMaintenanceController::destroy] Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus kegiatan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper Ambil Data Aset (ODP & ODC) untuk Pilihan Dropdown Modal
     */
    public function getAssets(Request $request)
    {
        $areaId = $request->input('area_id');

        $odpQuery = Odp::select('id', 'name', 'area_id')->orderBy('name');
        $odcQuery = Odc::select('id', 'name', 'area_id')->orderBy('name');

        if ($areaId) {
            $odpQuery->where('area_id', $areaId);
            $odcQuery->where('area_id', $areaId);
        }

        return response()->json([
            'odps' => $odpQuery->limit(200)->get(),
            'odcs' => $odcQuery->limit(100)->get(),
        ]);
    }
}

