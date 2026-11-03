<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransactionItem;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Perbaiki 2 item transaksi instalasi historis (TX #1657 & TX #1664) untuk Frecon 150m (ID 276)
        // yang sebelumnya tercatat 1.00 meter (maksudnya 1 roll = 150 meter) sebelum fitur auto-konversi roll aktif.
        $frecon150 = Material::find(276);
        if ($frecon150 && $frecon150->meter_per_roll > 0) {
            $mpr = (float) $frecon150->meter_per_roll; // 150
            $ppuMeter = ($frecon150->selling_price ?: ($frecon150->price_per_unit ?? 0)) / $mpr;

            $brokenCableItems = MaterialTransactionItem::where('material_id', 276)
                ->whereIn('material_transaction_id', [1657, 1664])
                ->where('quantity', '<=', 5)
                ->get();

            foreach ($brokenCableItems as $bItem) {
                $newQty = (float) $bItem->quantity * $mpr;
                $bItem->update([
                    'quantity' => $newQty,
                    'unit' => 'meter',
                    'price_per_unit' => $ppuMeter,
                    'total_price' => $ppuMeter * $newQty,
                ]);
            }
        }

        // Perbaiki unit Pigtail 50cm pada TX #1649 yang sempat tertulis 'cm' menjadi 'pcs'
        MaterialTransactionItem::where('material_id', 278)
            ->where('unit', 'cm')
            ->update(['unit' => 'pcs']);

        // 2. Rekonsiliasi penuh seluruh Material Aktif (ID > 260) berdasarkan buku besar (ledger) transaksi
        $materials = Material::where('id', '>', 260)->get();

        foreach ($materials as $mat) {
            $mpr = (float) ($mat->meter_per_roll ?? 0);
            $ppp = (float) ($mat->pcs_per_pack ?? 0);
            $cpp = (float) ($mat->cm_per_pcs ?? 0);
            $isCable = str_contains(strtolower((string) $mat->category), 'kabel') || $mpr > 0;

            $areaStocks = MaterialStock::where('material_id', $mat->id)->get();

            foreach ($areaStocks as $ms) {
                $areaId = $ms->area_id;

                if (!$mat->is_migration) {
                    // Untuk material Katalog Toko: initial_stock area adalah total dari PO Toko yang disetujui untuk area tersebut
                    $poItems = MaterialTransactionItem::where('material_id', $mat->id)
                        ->whereHas('transaction', function ($q) use ($areaId) {
                            $q->where('type', 'in')
                              ->where('status', 'approved')
                              ->where('area_id', $areaId)
                              ->where(function ($sq) {
                                  $sq->where('transaction_number', 'like', 'PO-%')
                                     ->orWhere('purpose', 'Pembelian Toko / Supplier');
                              });
                        })
                        ->get();

                    $poTotal = 0.0;
                    foreach ($poItems as $pi) {
                        $q = (float) $pi->quantity;
                        $u = strtolower((string) ($pi->unit ?? $mat->unit));
                        if ($isCable && in_array($u, ['roll', 'rol']) && $mpr > 0) {
                            $q *= $mpr;
                        }
                        $poTotal += $q;
                    }

                    if ($poTotal > 0) {
                        $ms->initial_stock = max((float) $ms->initial_stock, $poTotal);
                    }
                    // Khusus ONT ZVOLIN P613s (ID 282) di BC 2 (area_id 1): terdapat 12 pcs PO + 2 pcs tambah stok langsung = 14 pcs
                    if ($mat->id === 282 && (int) $areaId === 1 && (float) $ms->initial_stock < 14) {
                        $ms->initial_stock = 14;
                    }
                }

                // Hitung total pemakaian keluar (OUT, approved, non-migrasi) di area ini
                $outItems = MaterialTransactionItem::where('material_id', $mat->id)
                    ->whereHas('transaction', function ($q) use ($areaId) {
                        $q->where('type', 'out')
                          ->where('status', 'approved')
                          ->where('area_id', $areaId)
                          ->where('transaction_number', 'not like', '%MIG%');
                    })
                    ->get();

                $outTotal = 0.0;
                foreach ($outItems as $oi) {
                    $q = (float) $oi->quantity;
                    $u = strtolower((string) ($oi->unit ?? $mat->unit));
                    if ($isCable && in_array($u, ['roll', 'rol']) && $mpr > 0) {
                        $q *= $mpr;
                    }
                    $outTotal += $q;
                }

                // Hitung total pengembalian masuk (RTR-*, approved, Layak Pakai) di area ini
                $retItems = MaterialTransactionItem::where('material_id', $mat->id)
                    ->where('condition', 'Layak Pakai')
                    ->whereHas('transaction', function ($q) use ($areaId) {
                        $q->where('type', 'in')
                          ->where('status', 'approved')
                          ->where('area_id', $areaId)
                          ->where('transaction_number', 'like', 'RTR-%');
                    })
                    ->get();

                $retTotal = 0.0;
                foreach ($retItems as $ri) {
                    $q = (float) $ri->quantity;
                    $u = strtolower((string) ($ri->unit ?? $mat->unit));
                    if ($isCable && in_array($u, ['roll', 'rol']) && $mpr > 0) {
                        $q *= $mpr;
                    }
                    $retTotal += $q;
                }

                $reconciledStock = max(0.0, (float) $ms->initial_stock - $outTotal + $retTotal);
                $ms->stock = $reconciledStock;
                $ms->total_rolls = ($mpr > 0) ? ($reconciledStock / $mpr) : 0;
                $ms->total_packs = ($ppp > 0) ? floor($reconciledStock / $ppp) : 0;
                $ms->total_pieces = ($cpp > 0) ? ($reconciledStock / $cpp) : (($mpr == 0 && $ppp == 0) ? $reconciledStock : 0);
                $ms->save();
            }

            // Sinkronkan tabel master `materials`
            $refreshedAreaStocks = MaterialStock::where('material_id', $mat->id)->get();
            if ($mat->is_migration) {
                // Material Fisik Area (Bekas Layak Pakai): master stock = jumlah stok seluruh area
                $totalInit = (float) $refreshedAreaStocks->sum('initial_stock');
                $totalRemain = (float) $refreshedAreaStocks->sum('stock');
                if ($totalInit > 0) {
                    $mat->initial_stock = $totalInit;
                }
                $mat->stock = max(0.0, $totalRemain);
                $mat->recalcUnitTotals()->save();
            } else {
                // Material Katalog Gudang Pusat (/products): master stock = initial_stock Gudang Pusat dikurangi total PO yang sudah diambil area
                $totalOrderedByAreas = (float) $refreshedAreaStocks->sum('initial_stock');
                if ($totalOrderedByAreas > (float) $mat->initial_stock) {
                    $mat->initial_stock = $totalOrderedByAreas;
                }
                if ((float) $mat->initial_stock > 0) {
                    $mat->stock = max(0.0, (float) $mat->initial_stock - $totalOrderedByAreas);
                    $mat->recalcUnitTotals()->save();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
