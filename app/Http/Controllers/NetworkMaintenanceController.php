<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NetworkMaintenance;
use App\Models\Area;
use App\Models\Odp;
use App\Models\Odc;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class NetworkMaintenanceController extends Controller
{
    /**
     * Simpan Kegiatan Pemeliharaan & Perapihan Jalur Baru
     */
    public function store(Request $request)
    {
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
            'photo_before' => 'nullable|image|max:10240',
            'photo_after' => 'nullable|image|max:10240',
        ], [
            'category.required' => 'Jenis kegiatan wajib dipilih.',
            'title.required' => 'Judul kegiatan wajib diisi.',
            'scheduled_date.required' => 'Tanggal pelaksanaan wajib diisi.',
        ]);

        try {
            DB::beginTransaction();

            $photoBeforePath = null;
            if ($request->hasFile('photo_before')) {
                $photoBeforePath = $request->file('photo_before')->store('maintenance_photos', 'public');
            }

            $photoAfterPath = null;
            if ($request->hasFile('photo_after')) {
                $photoAfterPath = $request->file('photo_after')->store('maintenance_photos', 'public');
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kegiatan pemeliharaan/perapihan jalur berhasil ditambahkan!',
                'maintenance' => $maintenance->load(['area', 'odp', 'odc', 'technicians']),
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
            'photo_before' => 'nullable|image|max:10240',
            'photo_after' => 'nullable|image|max:10240',
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

            // Upload foto baru jika ada
            if ($request->hasFile('photo_before')) {
                if ($maintenance->photo_before && Storage::disk('public')->exists($maintenance->photo_before)) {
                    Storage::disk('public')->delete($maintenance->photo_before);
                }
                $updateData['photo_before'] = $request->file('photo_before')->store('maintenance_photos', 'public');
            }

            if ($request->hasFile('photo_after')) {
                if ($maintenance->photo_after && Storage::disk('public')->exists($maintenance->photo_after)) {
                    Storage::disk('public')->delete($maintenance->photo_after);
                }
                $updateData['photo_after'] = $request->file('photo_after')->store('maintenance_photos', 'public');
            }

            $maintenance->update($updateData);

            if (isset($validated['technician_ids'])) {
                $maintenance->technicians()->sync($validated['technician_ids']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Status pemeliharaan jalur berhasil diperbarui!',
                'maintenance' => $maintenance->fresh(['area', 'odp', 'odc', 'technicians']),
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
            if ($maintenance->photo_before && Storage::disk('public')->exists($maintenance->photo_before)) {
                Storage::disk('public')->delete($maintenance->photo_before);
            }
            if ($maintenance->photo_after && Storage::disk('public')->exists($maintenance->photo_after)) {
                Storage::disk('public')->delete($maintenance->photo_after);
            }

            $maintenance->technicians()->detach();
            $maintenance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Kegiatan pemeliharaan jalur berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
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

