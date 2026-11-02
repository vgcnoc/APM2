<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\TechnicianSchedule;
use App\Models\Customer;
use App\Models\Material;
use App\Models\MaterialStock;
use App\Models\MaterialTransaction;
use App\Models\MaterialTransactionItem;
use App\Models\Ont;
use App\Models\Area;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cari jadwal instalasi aktif yang belum memiliki transaksi OUT-BEKAL atau OUT-INSTALASI
        $schedules = TechnicianSchedule::where('type', 'installation')
            ->whereIn('status', ['scheduled', 'on_the_way', 'working'])
            ->with(['customer', 'technician'])
            ->get()
            ->unique('customer_id');

        foreach ($schedules as $sched) {
            $customer = $sched->customer;
            if (!$customer) continue;

            $hasTrx = MaterialTransaction::where('customer_id', $customer->id)
                ->where(function ($q) {
                    $q->where('transaction_number', 'like', 'OUT-BEKAL-%')
                      ->orWhere('source_type', 'jadwal_instalasi')
                      ->orWhere('transaction_number', 'like', 'OUT-INSTALASI-%');
                })
                ->exists();

            if ($hasTrx) continue;

            $customerAreaId = $customer->area_id ?: ($sched->area_id ?: Area::where('name', $customer->area)->value('id'));
            if (!$customerAreaId) continue;

            $deductItems = [];
            $selectedOnt = null;

            if ($sched->notes) {
                $lines = preg_split('/\r\n|\r|\n/', (string) $sched->notes);
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if (empty($trimmed)) continue;

                    // Parse Material
                    if (preg_match('/^Material:\s*(.+?)\s*\(([\d.]+)\s*([a-zA-Z]+)\)$/i', $trimmed, $m)) {
                        $matName = trim($m[1]);
                        $qty = (float) $m[2];
                        $unitStr = trim($m[3]);

                        $nameLower = strtolower($matName);
                        $cleanName = trim(preg_replace('/\s*\(.*?\)\s*/', '', $nameLower));
                        $material = Material::where('name', 'like', "%{$cleanName}%")->first();
                        if (!$material && (str_contains($cleanName, 'kabel') || str_contains($cleanName, 'frecon'))) {
                            $material = Material::where('category', 'Kabel Drop')
                                ->orWhere('category', 'Kabel')
                                ->orWhere('name', 'like', '%kabel%')
                                ->orWhere('name', 'like', '%frecon%')->first();
                        }

                        if ($material) {
                            $unitStr = $material->unit ?: $unitStr;
                            if ($material->meter_per_roll > 0 && strtolower($unitStr) === 'meter') {
                                if ($qty > 0 && $qty < ($material->meter_per_roll / 2)) {
                                    $qty = $qty * $material->meter_per_roll;
                                }
                            }

                            $deductItems[] = [
                                'material' => $material,
                                'qty' => $qty,
                                'unit' => $unitStr,
                            ];
                        }
                    }

                    // Parse ONT
                    elseif (preg_match('/^ONT:\s*(.+?)(?:\\s*\\(SN:\\s*([^\\)]+)\\))?$/i', $trimmed, $m)) {
                        $snRaw = isset($m[2]) ? trim($m[2]) : '';
                        // Bisa berupa 'ZICGA57F7E99 / DISAH WARKIR'
                        $snParts = explode('/', $snRaw);
                        $cleanSn = trim($snParts[0]);

                        if (!empty($cleanSn)) {
                            $selectedOnt = Ont::where('serial_number', $cleanSn)->first();
                        }
                        if (!$selectedOnt && $customer->ont_id) {
                            $selectedOnt = Ont::find($customer->ont_id);
                        }
                    }
                }
            }

            // Jika ada ONT terpilih, tambahkan materialnya ke alokasi bekal
            if ($selectedOnt) {
                $ontMaterial = null;
                if ($selectedOnt->material_transaction_item_id) {
                    $mItem = MaterialTransactionItem::find($selectedOnt->material_transaction_item_id);
                    $ontMaterial = $mItem?->material;
                }
                if (!$ontMaterial) {
                    $ontMaterial = Material::where('category', 'ONT')
                        ->where(function ($q) use ($selectedOnt) {
                            $brand = trim($selectedOnt->brand ?? '');
                            $model = trim($selectedOnt->model ?? '');
                            if ($brand && $model) {
                                $q->where('name', 'like', "%{$brand}%")->where('name', 'like', "%{$model}%");
                            } elseif ($brand) {
                                $q->where('name', 'like', "%{$brand}%");
                            }
                        })->first();
                }

                if ($ontMaterial) {
                    $alreadyIn = false;
                    foreach ($deductItems as $di) {
                        if ($di['material']->id === $ontMaterial->id) {
                            $alreadyIn = true;
                            break;
                        }
                    }
                    if (!$alreadyIn) {
                        $deductItems[] = [
                            'material' => $ontMaterial,
                            'qty' => 1,
                            'unit' => 'pcs',
                        ];
                    }
                }

                // Pastikan ONT terhubung ke customer_id
                if ((int) $selectedOnt->customer_id !== (int) $customer->id) {
                    $selectedOnt->update(['customer_id' => $customer->id]);
                }
            }

            if (!empty($deductItems)) {
                $techName = $sched->technician ? $sched->technician->name : 'Teknisi';
                $transactionOut = MaterialTransaction::create([
                    'transaction_number' => 'OUT-BEKAL-' . date('YmdHis') . '-' . $customer->id,
                    'type' => 'out',
                    'status' => 'approved',
                    'date' => $sched->scheduled_date ?: now(),
                    'technician_name' => $techName,
                    'purpose' => 'Bekal Material Jadwal Pasang Pelanggan ' . $customer->name . ' (' . ($customer->customer_id ?? 'ID:' . $customer->id) . ')',
                    'source_type' => 'jadwal_instalasi',
                    'source_id' => $customer->id,
                    'source_reference' => $customer->customer_id ?? ('CUST-' . $customer->id),
                    'user_id' => $sched->technician_id ?: 1,
                    'customer_id' => $customer->id,
                    'area_id' => $customerAreaId,
                    'notes' => "Rincian Bekal Material Instalasi:\n" . implode("\n", array_map(function($di) {
                        return "- " . $di['material']->name . ": " . $di['qty'] . " " . $di['unit'];
                    }, $deductItems)),
                ]);

                $totalCost = 0;
                foreach ($deductItems as $di) {
                    $mat = $di['material'];
                    $qty = (float) $di['qty'];
                    $unit = $di['unit'];

                    $matStock = MaterialStock::firstOrCreate(
                        ['material_id' => $mat->id, 'area_id' => $customerAreaId],
                        ['stock' => 0, 'initial_stock' => 0, 'total_rolls' => 0, 'total_packs' => 0, 'total_pieces' => 0]
                    );

                    $stockBefore = (float) $matStock->stock;
                    $stockAfter = max(0, $stockBefore - $qty);

                    $ppu = $mat->selling_price ?: ($mat->price_per_unit ?? 0);
                    if (strtolower($mat->category ?? '') === 'kabel' && strtolower($unit) === 'meter') {
                        $mpr = $mat->meter_per_roll > 0 ? $mat->meter_per_roll : 1000;
                        $ppu = $ppu / $mpr;
                    }
                    $itemTotal = $ppu * $qty;
                    $totalCost += $itemTotal;

                    MaterialTransactionItem::create([
                        'material_transaction_id' => $transactionOut->id,
                        'material_id' => $mat->id,
                        'quantity' => $qty,
                        'unit' => $unit,
                        'price_per_unit' => $ppu,
                        'total_price' => $itemTotal,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'condition' => 'Bekal Instalasi',
                    ]);

                    $matStock->update(['stock' => $stockAfter]);
                    if ($mat->meter_per_roll > 0) {
                        $matStock->total_rolls = max(0, $matStock->stock / $mat->meter_per_roll);
                        $matStock->save();
                    }

                    // Sinkronkan stok master
                    $totalAreaStock = MaterialStock::where('material_id', $mat->id)->sum('stock');
                    $mat->update([
                        'stock' => max(0, $totalAreaStock),
                        'total_rolls' => $mat->meter_per_roll > 0 ? max(0, $totalAreaStock / $mat->meter_per_roll) : $mat->total_rolls,
                    ]);
                }

                $transactionOut->update(['total_cost' => $totalCost]);
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
