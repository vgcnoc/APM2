<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MaterialTransactionItem;
use App\Models\MaterialStock;
use App\Models\Material;
use Illuminate\Support\Facades\DB;

class FixStockBugCommand extends Command
{
    protected $signature = 'stock:fix-legacy-bug';
    protected $description = 'Fix legacy transactions that were saved without base unit conversion';

    public function handle()
    {
        $this->info("Starting legacy bug fix...");

        DB::transaction(function() {
            $items = MaterialTransactionItem::with(['material', 'transaction'])->get();
            $fixedCount = 0;

            foreach ($items as $item) {
                if (!$item->material || !$item->transaction) continue;
                
                $material = $item->material;
                $tx = $item->transaction;

                // Calculate what the REAL quantity should be based on unit
                $realQuantity = $item->quantity;
                $unit = strtolower($item->unit ?? '');
                
                if (str_contains(strtolower($material->category), 'kabel') && in_array($unit, ['roll', 'rol'])) {
                    $realQuantity = $item->quantity * ($material->meter_per_roll > 0 ? $material->meter_per_roll : 1000);
                }
                if ($material->category === 'Paku Klem' && in_array($unit, ['pack', 'bungkus'])) {
                    $realQuantity = $item->quantity * ($material->pcs_per_pack > 0 ? $material->pcs_per_pack : 1);
                }
                if ($material->category === 'Isolasi' && $unit === 'pcs') {
                    $realQuantity = $item->quantity * ($material->cm_per_pcs > 0 ? $material->cm_per_pcs : 50);
                }

                // If the real quantity is different from what was recorded
                $diff = abs($item->stock_after - $item->stock_before);
                
                // If the difference matches the RAW quantity, but the REAL quantity should be higher, it's a bugged row
                if ($diff == $item->quantity && $realQuantity > $item->quantity) {
                    $this->info("Found bugged item ID {$item->id} in tx {$tx->transaction_number} (diff: {$diff}, should be: {$realQuantity})");
                    
                    $missingAmount = $realQuantity - $item->quantity;

                    // Fix item's stock_after (assuming stock_before is correct)
                    $item->stock_after = $item->stock_before + ($tx->type === 'in' ? $realQuantity : -$realQuantity);
                    $item->save();

                    // Fix Area Stock
                    if ($tx->area_id) {
                        $stockModel = MaterialStock::firstOrCreate(
                            ['material_id' => $material->id, 'area_id' => $tx->area_id],
                            ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
                        );
                        
                        if ($tx->type === 'in') {
                            $stockModel->stock += $missingAmount;
                        } else {
                            $stockModel->stock -= $missingAmount;
                        }

                        if (str_contains(strtolower($material->category), 'kabel') && $material->meter_per_roll > 0) {
                            $stockModel->total_rolls = $stockModel->stock / $material->meter_per_roll;
                        }
                        if ($material->category === 'Paku Klem' && $material->pcs_per_pack > 0) {
                            $stockModel->total_packs = $stockModel->stock / $material->pcs_per_pack;
                        }
                        if ($material->category === 'Isolasi' && $material->cm_per_pcs > 0) {
                            $stockModel->total_pieces = $stockModel->stock / $material->cm_per_pcs;
                        }
                        $stockModel->save();
                        $this->info("-> Adjusted Area Stock for Area ID {$tx->area_id} by {$missingAmount}");
                    }
                    
                    // Fix Global Stock (Gudang)
                    if ($tx->type === 'in') {
                        $material->stock -= $missingAmount;
                    } else {
                        $material->stock += $missingAmount;
                    }
                    
                    if (str_contains(strtolower($material->category), 'kabel') && $material->meter_per_roll > 0) {
                        $material->total_rolls = $material->stock / $material->meter_per_roll;
                    }
                    if ($material->category === 'Paku Klem' && $material->pcs_per_pack > 0) {
                        $material->total_packs = $material->stock / $material->pcs_per_pack;
                    }
                    if ($material->category === 'Isolasi' && $material->cm_per_pcs > 0) {
                        $material->total_pieces = $material->stock / $material->cm_per_pcs;
                    }
                    $material->save();
                    
                    $fixedCount++;
                }
            }

            $this->info("Fixed {$fixedCount} bugged transaction items.");
        });
    }
}
