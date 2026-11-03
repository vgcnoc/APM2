<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\TechnicianSchedule;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Pulihkan 5 item pemakaian instalasi Frecon 100m (ID 279) yang sebelumnya terhapus
        // saat material duplikat 'TEST FRECON 100M' (ID 274) dihapus (ON DELETE CASCADE).
        // - BC 2 (area_id = 1): TRX #1614 (INA MERLIANA), #1616 (REGI PRATAMA), #1617 (ASRI ANDRIANI), #1619 (H. Mahmud Kusaeri)
        // - BC 3 (area_id = 3): TRX #1650 (IDA)
        $frecon100 = Material::find(279);
        if ($frecon100) {
            $ppu100 = ($frecon100->selling_price ?: ($frecon100->price_per_unit ?? 75000)) / 100.0;
            $missingTrxIds = [1614, 1616, 1617, 1619, 1650];

            foreach ($missingTrxIds as $trxId) {
                $trx = MaterialTransaction::find($trxId);
                if (!$trx) {
                    continue;
                }

                $exists = MaterialTransactionItem::where('material_transaction_id', $trx->id)
                    ->where('material_id', 279)
                    ->exists();

                if (!$exists) {
                    MaterialTransactionItem::create([
                        'material_transaction_id' => $trx->id,
                        'material_id' => 279,
                        'quantity' => 100,
                        'unit' => 'meter',
                        'price_per_unit' => $ppu100,
                        'total_price' => $ppu100 * 100,
                        'stock_before' => 100,
                        'stock_after' => 0,
                        'condition' => 'Terpasang/Digunakan',
                    ]);
                }

                if ($trx->notes && str_contains($trx->notes, 'TEST FRECON 100M')) {
                    $trx->notes = str_replace(
                        ['TEST FRECON 100M: 1 meter', 'TEST FRECON 100M'],
                        ['Frecon 100m: 100 meter', 'Frecon 100m'],
                        $trx->notes
                    );
                    $trx->save();
                }
            }

            TechnicianSchedule::where('notes', 'like', '%TEST FRECON 100M%')
                ->each(function ($sched) {
                    $sched->notes = str_replace('TEST FRECON 100M', 'Frecon 100m', $sched->notes);
                    $sched->save();
                });
        }

        // 2. Perbaiki item kabel pada TRX #1652 (CUST #1588 JAIH, BC 2):
        // Sebelumnya tercatat ke Frecon 200m (ID 283, 200m) padahal memakai 1 roll Frecon 150m (ID 276, 150m).
        $frecon150 = Material::find(276);
        $trx1652 = MaterialTransaction::find(1652);
        if ($frecon150 && $trx1652) {
            $ppu150 = ($frecon150->selling_price ?: ($frecon150->price_per_unit ?? 100000)) / 150.0;
            $jaihCableItem = MaterialTransactionItem::where('material_transaction_id', 1652)
                ->where('material_id', 283)
                ->first();

            if ($jaihCableItem) {
                $jaihCableItem->update([
                    'material_id' => 276,
                    'quantity' => 150,
                    'unit' => 'meter',
                    'price_per_unit' => $ppu150,
                    'total_price' => $ppu150 * 150,
                    'stock_before' => 150,
                    'stock_after' => 0,
                ]);
            }

            if ($trx1652->notes && str_contains($trx1652->notes, 'Frecon 200m')) {
                $trx1652->notes = str_replace('Frecon 200m: 200 meter', 'Frecon 150m: 150 meter', $trx1652->notes);
                $trx1652->save();
            }
        }

        // 3. Perbaiki item kabel pada TRX #1649 (CUST #1619 Ela nurlaela, BC 3):
        // Sebelumnya mengarah ke material migrasi lama ID 1 (1 meter), arahkan ke ID 290 (kabel fo 1c - 20m, 20 meter).
        $trx1649Item = MaterialTransactionItem::where('material_transaction_id', 1649)
            ->where('material_id', 1)
            ->first();
        if ($trx1649Item && Material::find(290)) {
            $trx1649Item->update([
                'material_id' => 290,
                'quantity' => 20,
                'unit' => 'meter',
            ]);
        }

        // 4. Seluruh material migrasi / Bekas Layak Pakai (ID <= 260 atau is_migration = true, termasuk ID 289-304
        // seperti Adaptor 12v 1A (Bekas Layak Pakai) ID 294) sudah habis terpasang ke pelanggan -> stock = 0.
        MaterialStock::where(function ($q) {
            $q->where('material_id', '<=', 260)
              ->orWhereHas('material', fn ($mq) => $mq->where('is_migration', true));
        })->update([
            'stock' => 0,
            'total_rolls' => 0,
            'total_packs' => 0,
            'total_pieces' => 0,
        ]);

        Material::where('id', '<=', 260)
            ->orWhere('is_migration', true)
            ->update([
                'stock' => 0,
                'total_rolls' => 0,
                'total_packs' => 0,
                'total_pieces' => 0,
            ]);

        // 5. Bersihkan alokasi area nyasar (tanpa PO resmi) pada ONT H609e (ID 269) di BC 2 dan Adaptor 1.5A 12V (ID 305) di BC 2/BC 3
        MaterialStock::where('material_id', 269)->where('area_id', 1)->delete();
        MaterialStock::where('material_id', 305)->whereIn('area_id', [1, 3])->delete();

        // 6. Rekonsiliasi akurat untuk seluruh Material Katalog (ID > 260 dan bukan migrasi)
        $catalogMaterials = Material::where('id', '>', 260)
            ->where(function ($q) {
                $q->where('is_migration', false)->orWhereNull('is_migration');
            })
            ->get();

        foreach ($catalogMaterials as $mat) {
            $mpr = (float) ($mat->meter_per_roll ?? 0);
            $ppp = (float) ($mat->pcs_per_pack ?? 0);
            $cpp = (float) ($mat->cm_per_pcs ?? 0);
            $isCable = str_contains(strtolower((string) $mat->category), 'kabel') || $mpr > 0;

            // Pastikan setiap area yang pernah melakukan PO untuk material ini memiliki record MaterialStock
            $poAreaIds = MaterialTransaction::where('type', 'in')
                ->where('status', 'approved')
                ->whereNotNull('area_id')
                ->where(function ($sq) {
                    $sq->where('transaction_number', 'like', 'PO-%')
                       ->orWhere('purpose', 'Pembelian Toko / Supplier');
                })
                ->whereHas('items', fn ($iq) => $iq->where('material_id', $mat->id))
                ->pluck('area_id')
                ->unique();

            foreach ($poAreaIds as $pAreaId) {
                MaterialStock::firstOrCreate(
                    ['material_id' => $mat->id, 'area_id' => $pAreaId],
                    ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
                );
            }

            $areaStocks = MaterialStock::where('material_id', $mat->id)->get();

            foreach ($areaStocks as $ms) {
                $areaId = (int) $ms->area_id;

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
                    $ms->initial_stock = $poTotal;
                }

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

                if ($mat->id === 282 && $areaId === 1) {
                    $ms->initial_stock = 14;
                }

                $reconciledStock = max(0.0, (float) $ms->initial_stock - $outTotal + $retTotal);

                // Khusus ONT ZVOLIN P613s (ID 282) di BC 2: dikurangi 1 unit Rusak -> sisa sehat 3 pcs
                if ($mat->id === 282 && $areaId === 1) {
                    $reconciledStock = 3.0;
                }
                // Khusus ONT di BC 3 (area_id = 3): seluruh ONT sudah terpasang ke pelanggan (available = 0 di tabel onts)
                if (strtoupper((string) $mat->category) === 'ONT' && $areaId === 3) {
                    $reconciledStock = 0.0;
                }

                $ms->stock = $reconciledStock;
                $ms->total_rolls = ($mpr > 0) ? ($reconciledStock / $mpr) : 0;
                $ms->total_packs = ($ppp > 0) ? floor($reconciledStock / $ppp) : 0;
                $ms->total_pieces = ($cpp > 0) ? ($reconciledStock / $cpp) : (($mpr == 0 && $ppp == 0) ? $reconciledStock : 0);
                $ms->save();
            }

            // Sinkronkan master Gudang Pusat
            $refreshedAreaStocks = MaterialStock::where('material_id', $mat->id)->get();
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
