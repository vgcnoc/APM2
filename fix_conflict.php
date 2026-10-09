<?php
$content = file_get_contents('app/Http/Controllers/CustomerController.php');

$startMarker = '<<<<<<< ours';
$endMarker = '>>>>>>> theirs';

$lines = explode("\n", $content);
$start = -1;
$end = -1;

for ($i=0; $i<count($lines); $i++) {
    if ($start === -1 && strpos($lines[$i], '<<<<<<< ours') !== false) {
        $start = $i;
    }
    if (strpos($lines[$i], '>>>>>>> theirs') !== false) {
        $end = $i;
    }
}

if ($start !== -1 && $end !== -1) {
    $replacement = <<<'EOD'
            $schedules = $customer->technicianSchedules()->where('type', 'installation')->whereIn('status', ['scheduled', 'done'])->get();
            
            // Restore previous usage from old notes if this is an edit to prevent double-deduction
            $oldUsages = [];
            foreach ($schedules as $schedule) {
                if ($schedule->status === 'done' && $schedule->notes) {
                    $lines = explode("\n", $schedule->notes);
                    foreach ($lines as $line) {
                        if (str_starts_with(trim($line), 'Material:')) {
                            $mats = explode(',', str_replace('Material:', '', $line));
                            foreach ($mats as $mat) {
                                $mat = trim($mat);
                                if (preg_match('/^(.*?)\s*\((\d+(\.\d+)?)\s*(.*?)\)$/', $mat, $matches)) {
                                    $oldUsages[] = [
                                        'name' => trim($matches[1]),
                                        'qty' => (float)$matches[2]
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            // Restore old usages back to Area Stock
            foreach ($oldUsages as $old) {
                $nameLower = strtolower($old['name']);
                $material = \App\Models\Material::where('name', 'like', "%{$nameLower}%")->first();
                if (!$material && str_contains($nameLower, 'kabel')) {
                    $material = \App\Models\Material::where('category', 'Kabel Drop')
                        ->orWhere('category', 'Kabel')
                        ->orWhere('name', 'like', '%kabel%')->first();
                }
                if ($material) {
                    $materialStock = \App\Models\MaterialStock::where('material_id', $material->id)
                        ->where('area_id', $customer->area_id)
                        ->first();
                    if ($materialStock) {
                        $materialStock->increment('stock', $old['qty']);
                    }
                }
            }

            // Handle excess returned material
            if ($request->has('materials_returned') && is_array($request->materials_returned)) {
                $returnedItems = $request->materials_returned;
                if (count($returnedItems) > 0) {
                    $transaction = \App\Models\MaterialTransaction::create([
                        'transaction_number' => 'RTR-EXCESS-' . date('YmdHis'),
                        'type' => 'return',
                        'date' => now(),
                        'technician_name' => auth()->user()->name,
                        'purpose' => 'Pengembalian Kelebihan Material Instalasi Pelanggan ' . $customer->name,
                        'user_id' => auth()->id(),
                    ]);

                    foreach ($returnedItems as $item) {
                        if (!empty($item['returned_qty']) && $item['returned_qty'] > 0) {
                            $nameLower = strtolower($item['name']);
                            $material = \App\Models\Material::where('name', 'like', "%{$nameLower}%")->first();
                            if (!$material && str_contains($nameLower, 'kabel')) {
                                $material = \App\Models\Material::where('category', 'Kabel Drop')
                                    ->orWhere('category', 'Kabel')
                                    ->orWhere('name', 'like', '%kabel%')->first();
                            }

                            if ($material) {
                                \App\Models\MaterialTransactionItem::create([
                                    'material_transaction_id' => $transaction->id,
                                    'material_id' => $material->id,
                                    'quantity' => $item['returned_qty'],
                                    'unit' => str_contains($nameLower, 'kabel') ? 'm' : 'pcs',
                                    'price_per_unit' => $material->price_per_unit ?? 0,
                                    'total_price' => ($material->price_per_unit ?? 0) * $item['returned_qty'],
                                ]);
                                
                                // Retur langsung ke Gudang Utama (bukan ke Stok Area)
                                $material->increment('stock', $item['returned_qty']);
                            }
                        }
                    }
                }
            }

            // Handle material usage (deduct from Area Stock)
            $usageDetails = [];
            if ($request->has('materials_used') && is_array($request->materials_used)) {
                $usedItems = $request->materials_used;
                if (count($usedItems) > 0) {
                    foreach ($usedItems as $item) {
                        if (!empty($item['actual_qty']) && $item['actual_qty'] > 0) {
                            $nameLower = strtolower($item['name']);
                            
                            // Try to find material id based on name
                            $material = \App\Models\Material::where('name', 'like', "%{$nameLower}%")->first();
                            if (!$material && str_contains($nameLower, 'kabel')) {
                                $material = \App\Models\Material::where('category', 'Kabel Drop')
                                    ->orWhere('category', 'Kabel')
                                    ->orWhere('name', 'like', '%kabel%')->first();
                            }

                            if ($material) {
                                // Deduct from Area Stock
                                $materialStock = \App\Models\MaterialStock::where('material_id', $material->id)
                                    ->where('area_id', $customer->area_id)
                                    ->first();
                                    
                                if ($materialStock) {
                                    $materialStock->decrement('stock', $item['actual_qty']);
                                }

                                $unitStr = str_contains($nameLower, 'kabel') ? 'meter' : 'pcs';
                                $usageDetails[] = $item['name'] . ' (' . $item['actual_qty'] . ' ' . $unitStr . ')';
EOD;
    $newLines = array_slice($lines, 0, $start);
    $newLines[] = $replacement;
    $newLines = array_merge($newLines, array_slice($lines, $end + 1));
    file_put_contents('app/Http/Controllers/CustomerController.php', implode("\n", $newLines));
    echo "Fixed!\n";
} else {
    echo "Could not find markers.\n";
}
