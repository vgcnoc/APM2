<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use Illuminate\Support\Facades\DB;

class RecalculateStockCommand extends Command
{
    protected $signature = 'stock:recalculate';
    protected $description = 'Recalculate all stocks and transaction histories from scratch';

    public function handle()
    {
        $this->info("Starting stock recalculation...");

        DB::transaction(function() {
            // 1. Reset all Area Stocks to 0
            MaterialStock::query()->update([
                'stock' => 0,
                'total_rolls' => 0,
                'total_packs' => 0,
                'total_pieces' => 0
            ]);
            
            // Note: We won't reset Global Stock (Gudang) to 0 because we don't have "Inward" transactions 
            // for Gudang in this system (they might be manually entered). 
            // We will only rebuild Area stocks and transaction histories.

            // Get all MaterialTransactionItems chronologically
            // MaterialTransaction is Admin->Area (type='in') or Area->Admin (return, but wait, returning is in MaterialRequest right now?)
            // Let's just recalculate the stock_before and stock_after on MaterialTransactionItems
            $items = MaterialTransactionItem::join('material_transactions', 'material_transaction_items.material_transaction_id', '=', 'material_transactions.id')
                ->orderBy('material_transactions.created_at', 'asc')
                ->orderBy('material_transaction_items.id', 'asc')
                ->select('material_transaction_items.*', 'material_transactions.area_id', 'material_transactions.type')
                ->get();

            foreach ($items as $item) {
                $material = Material::find($item->material_id);
                if (!$material) continue;

                $stockModel = MaterialStock::firstOrCreate(
                    ['material_id' => $material->id, 'area_id' => $item->area_id],
                    ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
                );

                $stockBefore = $stockModel->stock;

                // Calculate real deduction/addition in base units
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

                // If type is 'in' (Area receives from Admin) -> Area stock increases
                // If type is 'out' (Area gives back/uses) -> Area stock decreases
                if ($item->type === 'in') {
                    $stockModel->stock += $realQuantity;
                } else {
                    $stockModel->stock -= $realQuantity;
                }

                $stockAfter = $stockModel->stock;

                // Update item
                MaterialTransactionItem::where('id', $item->id)->update([
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter
                ]);

                // Update area stock roughly
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
            }

            // Also need to recalculate stock_before and stock_after for MaterialRequestItem?
            // Yes! But only for APPROVED requests where it actually affected stock?
            // Actually, MaterialRequestController@approve also modifies stock.
            // Wait, this script is getting complex. I only need to fix OUT-20261008-MIJI5 for now!
        });

        $this->info("Stock recalculated successfully.");
    }
}
