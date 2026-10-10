<?php
$file = 'app/Http/Controllers/PurchaseOrderController.php';
$content = file_get_contents($file);
$find = "            \$transaction = MaterialTransaction::create([
                'transaction_number' => \$transactionNumber,
                'type' => 'in',
                'date' => \$validated['date'],
                'purpose' => 'Pembelian Toko / Supplier',
                'technician_name' => \$validated['area_name'], // Reusing this column for area/wilayah name
                'notes' => \$validated['notes'] ?? null,
                'user_id' => auth()->id(),
                'total_cost' => 0 // Will calculate below
            ]);";
$replace = "            \$area = \App\Models\Area::where('name', \$validated['area_name'])->first();

            \$transaction = MaterialTransaction::create([
                'transaction_number' => \$transactionNumber,
                'type' => 'in',
                'date' => \$validated['date'],
                'purpose' => 'Pembelian Toko / Supplier',
                'technician_name' => \$validated['area_name'], // Reusing this column for area/wilayah name
                'area_id' => \$area ? \$area->id : null,
                'area' => \$validated['area_name'],
                'notes' => \$validated['notes'] ?? null,
                'user_id' => auth()->id(),
                'total_cost' => 0 // Will calculate below
            ]);";

$find2 = str_replace("\r\n", "\n", $find);
$find3 = str_replace("\n", "\r\n", $find);

$content = str_replace($find, $replace, $content);
$content = str_replace($find2, $replace, $content);
$content = str_replace($find3, $replace, $content);

file_put_contents($file, $content);
echo "Done\n";
