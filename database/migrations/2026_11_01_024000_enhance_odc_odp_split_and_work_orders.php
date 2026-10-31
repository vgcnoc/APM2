<?php

use App\Models\InfrastructureWorkOrder;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Ont;
use App\Models\Survey;
use App\Services\InfrastructureWorkOrderService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('infrastructure_work_orders') && !Schema::hasColumn('infrastructure_work_orders', 'split_units')) {
            Schema::table('infrastructure_work_orders', function (Blueprint $table) {
                $table->json('split_units')->nullable()->after('notes');
            });
        }

        // 1. Sinkronkan flag is_split pada ODC & ODP
        if (Schema::hasTable('odcs') && Schema::hasColumn('odcs', 'is_split')) {
            DB::table('odcs')
                ->where('type', 'Split')
                ->orWhereNotNull('parent_odc_id')
                ->update(['is_split' => true]);

            $parentOdcIds = DB::table('odcs')->whereNotNull('parent_odc_id')->pluck('parent_odc_id')->unique()->values();
            if ($parentOdcIds->isNotEmpty()) {
                DB::table('odcs')->whereIn('id', $parentOdcIds)->update(['is_split' => true]);
            }
        }

        if (Schema::hasTable('odps') && Schema::hasColumn('odps', 'is_split')) {
            DB::table('odps')
                ->where('type', 'Split 2')
                ->orWhereNotNull('parent_odp_id')
                ->update(['is_split' => true]);

            $parentOdpIds = DB::table('odps')->whereNotNull('parent_odp_id')->pluck('parent_odp_id')->unique()->values();
            if ($parentOdpIds->isNotEmpty()) {
                DB::table('odps')->whereIn('id', $parentOdpIds)->update(['is_split' => true]);
            }
        }

        // 2. Sinkronkan pon_id pada ODC berdasarkan olt_id + pon_port agar Topologi Jaringan utuh
        if (Schema::hasTable('odcs') && Schema::hasTable('olt_pons') && Schema::hasColumn('odcs', 'pon_id')) {
            $odcsMissingPon = DB::table('odcs')
                ->whereNull('pon_id')
                ->whereNotNull('olt_id')
                ->whereNotNull('pon_port')
                ->get(['id', 'olt_id', 'pon_port']);

            foreach ($odcsMissingPon as $row) {
                $pon = \App\Models\OltPon::firstOrCreate(
                    ['olt_id' => $row->olt_id, 'port_number' => (int) $row->pon_port],
                    ['name' => 'PON ' . (int) $row->pon_port, 'capacity' => 128, 'status' => 'active']
                );

                if ($pon?->id) {
                    DB::table('odcs')->where('id', $row->id)->update(['pon_id' => $pon->id]);
                }
            }
        }

        // 3. Tautkan otomatis pasangan ODC Split yang dibuat bersamaan pada OLT & PON yang sama namun belum tertaut parent_odc_id
        if (Schema::hasTable('odcs') && Schema::hasColumn('odcs', 'parent_odc_id')) {
            $existingParentIds = DB::table('odcs')->whereNotNull('parent_odc_id')->pluck('parent_odc_id')->all();
            $unlinkedSplitOdcs = DB::table('odcs')
                ->where('is_split', true)
                ->whereNull('parent_odc_id')
                ->when(!empty($existingParentIds), fn ($q) => $q->whereNotIn('id', $existingParentIds))
                ->orderBy('id')
                ->get(['id', 'name', 'olt_id', 'pon_port', 'created_at']);

            $groupedOdcs = [];
            foreach ($unlinkedSplitOdcs as $item) {
                if (!$item->created_at || !$item->olt_id) {
                    continue;
                }
                $key = $item->olt_id . '|' . ($item->pon_port ?? '0') . '|' . substr((string) $item->created_at, 0, 16);
                $groupedOdcs[$key][] = $item;
            }

            foreach ($groupedOdcs as $pair) {
                if (count($pair) === 2) {
                    [$induk, $lanjutan] = $pair;
                    DB::table('odcs')->where('id', $lanjutan->id)->update([
                        'parent_odc_id' => $induk->id,
                        'is_split' => true,
                    ]);
                }
            }
        }

        // 4. Tautkan otomatis pasangan ODP Split 2 yang dibuat bersamaan pada ODC yang sama namun belum tertaut parent_odp_id
        if (Schema::hasTable('odps') && Schema::hasColumn('odps', 'parent_odp_id')) {
            $existingParentOdpIds = DB::table('odps')->whereNotNull('parent_odp_id')->pluck('parent_odp_id')->all();
            $unlinkedSplitOdps = DB::table('odps')
                ->where('is_split', true)
                ->whereNull('parent_odp_id')
                ->when(!empty($existingParentOdpIds), fn ($q) => $q->whereNotIn('id', $existingParentOdpIds))
                ->orderBy('id')
                ->get(['id', 'name', 'odc_id', 'created_at']);

            $groupedOdps = [];
            foreach ($unlinkedSplitOdps as $item) {
                if (!$item->created_at || !$item->odc_id) {
                    continue;
                }
                $key = $item->odc_id . '|' . substr((string) $item->created_at, 0, 16);
                $groupedOdps[$key][] = $item;
            }

            foreach ($groupedOdps as $pair) {
                if (count($pair) === 2) {
                    [$induk, $lanjutan] = $pair;
                    DB::table('odps')->where('id', $lanjutan->id)->update([
                        'parent_odp_id' => $induk->id,
                        'is_split' => true,
                    ]);
                }
            }
        }

        // 5. Backfill kolom JSON split_units pada Work Order lama & perbaiki WO Split 2 yang baru dikomisioning menjadi 1 unit kosong
        if (Schema::hasTable('infrastructure_work_orders')) {
            $splitWos = InfrastructureWorkOrder::whereNull('split_units')
                ->where('notes', 'like', '%[Split Unit 1:%')
                ->get();

            foreach ($splitWos as $wo) {
                $parsed = InfrastructureWorkOrderService::parseSplitUnitsFromNotes($wo->notes);
                if (count($parsed) >= 2) {
                    $wo->split_units = $parsed;
                    $wo->saveQuietly();

                    // Jika WO ODP Split 2 sudah completed tetapi baru menghasilkan 1 ODP tanpa pelanggan/survey, lengkapi menjadi pasangan Induk & Lanjutan
                    if (
                        $wo->status === 'completed'
                        && $wo->type === 'odp_deployment'
                        && $wo->created_odp_id
                        && Schema::hasTable('odps')
                    ) {
                        $createdOdp = Odp::find($wo->created_odp_id);
                        if (
                            $createdOdp
                            && !$createdOdp->parent_odp_id
                            && $createdOdp->children()->count() === 0
                            && (int) $createdOdp->used_ports === 0
                            && (!Schema::hasTable('onts') || Ont::where('odp_id', $createdOdp->id)->count() === 0)
                            && (!Schema::hasTable('surveys') || Survey::where('odp_id', $createdOdp->id)->count() === 0)
                        ) {
                            InfrastructureWorkOrderService::approveAndCommission($wo, $wo->approver);
                        }
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('infrastructure_work_orders') && Schema::hasColumn('infrastructure_work_orders', 'split_units')) {
            Schema::table('infrastructure_work_orders', function (Blueprint $table) {
                $table->dropColumn('split_units');
            });
        }
    }
};
