<?php

namespace App\Services;

use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\PgsMaterial;
use App\Models\PgkMaterial;
use App\Models\PgjMaterial;
use App\Models\InfrastructureWorkOrderMaterial;
use App\Models\Area;
use Illuminate\Support\Facades\DB;

class RestockStockService
{
    /**
     * Dapatkan daftar transaksi Order Toko (Restock) beserta kalkulasi sisa stok per item.
     * Mengambil HANYA dari transaksi restock/order toko, bukan dari produk master umum.
     *
     * @param int|null $areaId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRestockTransactions($areaId = null)
    {
        $query = MaterialTransaction::where(function ($q) {
                $q->where('purpose', 'Pembelian Toko / Supplier')
                  ->orWhere('transaction_number', 'like', 'PO-%');
            })
            ->where('type', 'in')
            ->with(['items.material.stocks', 'areaModel'])
            ->latest('id');

        if ($areaId) {
            $areaModel = Area::find($areaId);
            $areaName = $areaModel?->name;

            $query->where(function ($q) use ($areaId, $areaName) {
                $q->where('area_id', $areaId)
                  ->orWhereNull('area_id');

                if ($areaName) {
                    $q->orWhere('area', $areaName)
                      ->orWhere('technician_name', $areaName);
                }
            });
        }

        $transactions = $query->limit(200)->get();

        // Kumpulkan semua ID item transaksi restock
        $itemIds = $transactions->pluck('items')->flatten()->pluck('id')->filter()->unique()->toArray();

        if (!empty($itemIds)) {
            // Hitung alokasi di PGS (tiket yang masih berjalan atau telah terpakai)
            $pgsUsed = PgsMaterial::whereIn('material_transaction_item_id', $itemIds)
                ->select('material_transaction_item_id', DB::raw('SUM(GREATEST(0, quantity_prepared - quantity_returned)) as total_used'))
                ->groupBy('material_transaction_item_id')
                ->pluck('total_used', 'material_transaction_item_id');

            // Hitung alokasi di PGK (tiket yang masih berjalan atau telah terpakai)
            $pgkUsed = PgkMaterial::whereIn('material_transaction_item_id', $itemIds)
                ->select('material_transaction_item_id', DB::raw('SUM(GREATEST(0, quantity_prepared - quantity_returned)) as total_used'))
                ->groupBy('material_transaction_item_id')
                ->pluck('total_used', 'material_transaction_item_id');

            // Hitung alokasi di PGJ (Jalur) jika modul tersedia
            $pgjUsed = class_exists(PgjMaterial::class)
                ? PgjMaterial::whereIn('material_transaction_item_id', $itemIds)
                    ->select('material_transaction_item_id', DB::raw('SUM(GREATEST(0, quantity_prepared - quantity_returned)) as total_used'))
                    ->groupBy('material_transaction_item_id')
                    ->pluck('total_used', 'material_transaction_item_id')
                : collect();

            // Hitung alokasi di Work Order Infrastruktur (ODC / ODP) jika modul tersedia
            $woUsed = class_exists(InfrastructureWorkOrderMaterial::class)
                ? InfrastructureWorkOrderMaterial::whereIn('material_transaction_item_id', $itemIds)
                    ->select('material_transaction_item_id', DB::raw('SUM(requested_qty) as total_used'))
                    ->groupBy('material_transaction_item_id')
                    ->pluck('total_used', 'material_transaction_item_id')
                : collect();

            foreach ($transactions as $trx) {
                foreach ($trx->items as $item) {
                    $used = (float) ($pgsUsed[$item->id] ?? 0)
                          + (float) ($pgkUsed[$item->id] ?? 0)
                          + (float) ($pgjUsed[$item->id] ?? 0)
                          + (float) ($woUsed[$item->id] ?? 0);

                    $origQty = (float) $item->quantity;
                    $item->allocated_quantity = $used;
                    $item->remaining_quantity = max(0, $origQty - $used);
                }
            }
        }

        return $transactions;
    }
}
