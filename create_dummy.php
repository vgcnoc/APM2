<?php
$pkg = \App\Models\InternetPackage::first();
$pkgId = $pkg ? $pkg->id : null;
\App\Models\Customer::create([
    'customer_code' => 'DUMMY-' . rand(1000, 9999),
    'name' => 'Bapak Dummy',
    'email' => 'dummy@example.com',
    'phone' => '081234567890',
    'address' => 'Jl. Dummy No. ' . rand(1, 100) . ', RT/RW 01/02, Kel. Percobaan',
    'package_id' => $pkgId,
    'base_amount' => 200000,
    'installation_fee' => 150000,
    'status' => 'survey',
    'registration_date' => now(),
    'notes' => 'Ini data dummy untuk percobaan survey',
]);
echo "Dummy local created successfully\n";
