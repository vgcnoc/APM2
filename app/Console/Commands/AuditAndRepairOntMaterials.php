<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\CustomerInstalledMaterial;
use App\Models\CustomerMaterialDismantle;
use App\Models\MaterialTransactionItem;
use App\Services\CustomerMaterialService;
use App\Services\MaterialService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditAndRepairOntMaterials extends Command
{
    protected $signature = 'audit:repair-ont-materials {--dry-run : Only display what would be changed without saving}';
    protected $description = 'Audit and repair erroneously mapped ONT materials and dismantle stocks';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? "=== RUNNING IN DRY-RUN MODE (NO CHANGES WILL BE WRITTEN) ===" : "=== EXECUTING ONT MATERIAL AUDIT & REPAIR ===");

        // 1. Audit & Koreksi CustomerInstalledMaterial yang salah mapping ke Material ID 2 (ONT Zvolin XPON)
        $this->info("\n--- 1. AUDIT CUSTOMER INSTALLED MATERIALS (MAT ID 2) ---");
        $installedOnMat2 = CustomerInstalledMaterial::where('material_id', 2)->get();
        $repairedCim = 0;

        foreach ($installedOnMat2 as $cim) {
            $name = (string) $cim->item_name;
            // Jika nama barang jelas-jelas bukan Zvolin
            if (!str_contains(strtolower($name), 'zvolin')) {
                // Ekstrak nama
                $resolved = CustomerMaterialService::resolveOntMaterial($name, $name);
                if ($resolved && $resolved->id != 2) {
                    $this->line("CIM #{$cim->id} [{$cim->customer_id}] '{$name}' -> Re-mapped to Material ID {$resolved->id} ({$resolved->name})");
                    if (!$dryRun) {
                        $cim->material_id = $resolved->id;
                        $cim->save();
                    }
                    $repairedCim++;
                }
            }
        }
        $this->info("Total CustomerInstalledMaterial yang dire-map: {$repairedCim}");

        // 2. Audit & Koreksi Transaksi Serah Terima Bongkaran Pahrudin (H609e / H690e) yang salah masuk ke ONT Zvolin XPON di BC 1
        $this->info("\n--- 2. KOREKSI DISMANTLE PAHRUDIN (H609e/H690e RUSAK) DI BC 1 ---");
        $dismantlePahrudin = CustomerMaterialDismantle::where('id', 29)->first();
        if ($dismantlePahrudin && $dismantlePahrudin->material_id == 2) {
            $this->warn("Dismantle #29 [Pahrudin] '{$dismantlePahrudin->item_name}' tercatat sebagai MatID 2 (ONT Zvolin XPON)");
            
            // Cari material H690e/H609e
            $targetMat = Material::where('name', 'like', '%H690e%')
                ->orWhere('name', 'like', '%H609e%')
                ->first();

            $targetMatId = $targetMat ? $targetMat->id : 286;

            if (!$dryRun) {
                DB::transaction(function () use ($dismantlePahrudin, $targetMatId) {
                    $dismantlePahrudin->material_id = $targetMatId;
                    $dismantlePahrudin->save();

                    // Update transaksi item #4414 (RET-DISM-PGK-20261119-007)
                    $txItem = MaterialTransactionItem::find(4414);
                    if ($txItem) {
                        $txItem->material_id = $targetMatId;
                        $txItem->material_name = 'ONT H690e ( Non Adaptor )';
                        $txItem->condition = 'rusak_scrap';
                        $txItem->save();
                    }

                    // Kurangi 1 unit dari stok ONT Zvolin XPON (ID 2) di Area BC 1 (Area ID 1)
                    $zvolinBc1 = MaterialStock::where('material_id', 2)->where('area_id', 1)->first();
                    if ($zvolinBc1 && $zvolinBc1->stock > 0) {
                        $zvolinBc1->stock = max(0, $zvolinBc1->stock - 1);
                        $zvolinBc1->save();
                        Log::info("[AuditRepair] Stok ONT Zvolin XPON di BC 1 dikurangi 1 menjadi {$zvolinBc1->stock} karena pengembalian H690e Pahrudin.");
                    }
                });
                $this->info("Koreksi Dismantle Pahrudin & Stok BC 1 berhasil!");
            } else {
                $this->info("[DRY-RUN] Akan mengurangi 1 unit dari stok ONT Zvolin XPON di BC 1 dan memindahkan ke MatID {$targetMatId}");
            }
        } else {
            $this->info("Dismantle Pahrudin sudah sesuai atau tidak ada.");
        }

        // 3. Audit & Koreksi Transaksi Cabut Heriyandi (ZimmLink) yang salah masuk ke ONT Zvolin XPON di BC 3
        $this->info("\n--- 3. KOREKSI DISMANTLE HERIYANDI (ZIMMLINK) DI BC 3 ---");
        $dismantleHeriyandi = CustomerMaterialDismantle::where('id', 27)->first();
        if ($dismantleHeriyandi && $dismantleHeriyandi->material_id == 2) {
            $this->warn("Dismantle #27 [Heriyandi] '{$dismantleHeriyandi->item_name}' tercatat sebagai MatID 2 (ONT Zvolin XPON)");

            $zimmMat = Material::where('name', 'like', '%Zimm Link XPON%')->first()
                ?: Material::where('name', 'like', '%Zimm Link%')->first();
            $zimmMatId = $zimmMat ? $zimmMat->id : 78;

            if (!$dryRun) {
                DB::transaction(function () use ($dismantleHeriyandi, $zimmMatId) {
                    $dismantleHeriyandi->material_id = $zimmMatId;
                    $dismantleHeriyandi->save();

                    // Update transaksi item #4406 (RTR-CBP-20261119-003)
                    $txItem = MaterialTransactionItem::find(4406);
                    if ($txItem) {
                        $txItem->material_id = $zimmMatId;
                        $txItem->save();
                    }

                    // Kurangi 1 unit dari ONT Zvolin XPON (ID 2) di BC 3 (Area ID 3)
                    $zvolinBc3 = MaterialStock::where('material_id', 2)->where('area_id', 3)->first();
                    if ($zvolinBc3 && $zvolinBc3->stock > 0) {
                        $zvolinBc3->stock = max(0, $zvolinBc3->stock - 1);
                        $zvolinBc3->save();
                    }

                    // Tambah 1 unit ke ONT Zimm Link (ID 78) di BC 3
                    $zimmBc3 = MaterialStock::firstOrCreate(
                        ['material_id' => $zimmMatId, 'area_id' => 3],
                        ['stock' => 0, 'initial_stock' => 0]
                    );
                    $zimmBc3->stock += 1;
                    $zimmBc3->save();

                    Log::info("[AuditRepair] Stok ONT Zvolin XPON di BC 3 dikurangi 1 dan dipindahkan ke Zimm Link (ID {$zimmMatId})");
                });
                $this->info("Koreksi Dismantle Heriyandi & Stok BC 3 berhasil!");
            } else {
                $this->info("[DRY-RUN] Akan memindahkan 1 unit dari ONT Zvolin XPON ke Zimm Link di Area BC 3");
            }
        } else {
            $this->info("Dismantle Heriyandi sudah sesuai atau tidak ada.");
        }

        // 4. Sinkronisasi Total Stok Master (Material) dengan Jumlah Stok Per Area
        if (!$dryRun) {
            $this->info("\n--- 4. SINKRONISASI STOK MASTER ---");
            $affectedMatIds = [2, 78, 286, 345];
            foreach ($affectedMatIds as $mId) {
                $m = Material::find($mId);
                if ($m) {
                    $totalAreaStock = (float) MaterialStock::where('material_id', $m->id)->sum('stock');
                    $m->stock = max(0.0, $totalAreaStock);
                    $m->save();
                    $this->line("Material '{$m->name}' (ID: {$m->id}) total stock disinkronkan ke: {$m->stock} {$m->unit}");
                }
            }
        }

        $this->info("\n=== AUDIT & REPAIR SELESAI ===");
        return 0;
    }
}
