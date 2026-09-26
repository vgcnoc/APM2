<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\TechnicianSchedule;
use App\Models\User;
use App\Models\InternetPackage;
use App\Models\Olt;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Survey;

class DummyCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');
        $packages = InternetPackage::all();
        $technicians = User::where('role', 'teknisi')->get();

        if ($packages->isEmpty() || $technicians->isEmpty()) {
            $this->command->error('Pastikan paket internet dan teknisi sudah ada di database.');
            return;
        }

        // 1. Buat Dummy Infrastruktur (OLT, ODC, ODP)
        $olt = Olt::create([
            'name' => 'OLT Pusat',
            'ip_address' => '10.10.10.1',
            'total_pon_ports' => 8,
            'description' => 'OLT Utama',
            'status' => 'active'
        ]);

        $odc = Odc::create([
            'olt_id' => $olt->id,
            'name' => 'ODC-01-A',
            'location' => 'Jl. Merdeka Raya',
            'capacity' => 144,
            'status' => 'active'
        ]);

        $odp1 = Odp::create([
            'odc_id' => $odc->id,
            'name' => 'ODP-01-A-01',
            'total_ports' => 8,
            'used_ports' => 2,
            'status' => 'active',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
        ]);

        // 2. Buat Dummy Data Booking (Full Data Format)
        for ($i = 0; $i < 10; $i++) {
            $areas = ['Area 1', 'Area 2'];
            $area = $areas[array_rand($areas)];
            
            $addressDetail = $faker->streetAddress;
            $rtRw = $faker->numerify('0#/0#');
            $kelurahan = $faker->citySuffix;
            $kecamatan = $faker->city;
            
            $fullAddress = "{$addressDetail}, RT/RW: {$rtRw}, Kel. {$kelurahan}, Kec. {$kecamatan}";
            
            Customer::create([
                'customer_code' => 'CUST-' . strtoupper($faker->bothify('???####')),
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'address' => $fullAddress,
                'area' => $area,
                'identity_photo' => null, // Left null to simulate real form behavior without actual image file, or we can use a dummy image. Let's use null for now.
                'latitude' => $faker->latitude(-9, 6),
                'longitude' => $faker->longitude(95, 141),
                'package_id' => $packages->random()->id,
                'status' => 'booking',
                'notes' => 'Calon pelanggan dari referensi sosmed, minta segera dihubungi untuk pemasangan.',
            ]);
        }

        // 3. Buat Dummy Data Survey (Belum Dijadwalkan)
        for ($i = 0; $i < 2; $i++) {
            Customer::create([
                'customer_code' => 'CUST-' . strtoupper($faker->bothify('???####')),
                'name' => 'Menunggu Jadwal ' . $faker->firstName,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'address' => $faker->address,
                'latitude' => $faker->latitude(-9, 6),
                'longitude' => $faker->longitude(95, 141),
                'package_id' => $packages->random()->id,
                'status' => 'survey',
                'notes' => 'Calon pelanggan dari referensi teman',
            ]);
        }

        // 4. Buat Dummy Data Survey (Sudah Dijadwalkan)
        for ($i = 0; $i < 2; $i++) {
            $customer = Customer::create([
                'customer_code' => 'CUST-' . strtoupper($faker->bothify('???####')),
                'name' => 'Jadwal Survey ' . $faker->firstName,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'address' => $faker->address,
                'latitude' => $faker->latitude(-9, 6),
                'longitude' => $faker->longitude(95, 141),
                'package_id' => $packages->random()->id,
                'status' => 'survey',
                'notes' => 'Segera jadwalkan kunjungannya',
            ]);

            TechnicianSchedule::create([
                'ticket_id' => null,
                'technician_id' => $technicians->random()->id,
                'customer_id' => $customer->id,
                'scheduled_date' => now()->addDays(rand(1, 3))->toDateString(),
                'scheduled_time' => sprintf('%02d:00', rand(9, 15)),
                'type' => 'survey',
                'status' => 'scheduled',
                'notes' => 'Jadwal survey dari sistem',
            ]);
        }

        // 5. Buat Dummy Data Survey SELESAI (Feasible / Ready Install)
        for ($i = 0; $i < 2; $i++) {
            $tech = $technicians->random();
            $customer = Customer::create([
                'customer_code' => 'CUST-' . strtoupper($faker->bothify('???####')),
                'name' => 'Ready Install ' . $faker->firstName,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'address' => $faker->address,
                'latitude' => $faker->latitude(-9, 6),
                'longitude' => $faker->longitude(95, 141),
                'package_id' => $packages->random()->id,
                'status' => 'survey', // Status tetap survey, nunggu diinstal
                'notes' => 'Sudah disurvey dan layak pasang',
            ]);

            Survey::create([
                'customer_id' => $customer->id,
                'odp_id' => $odp1->id,
                'surveyor_id' => $tech->id,
                'distance_meters' => rand(50, 200),
                'port_available' => true,
                'feasibility' => 'feasible',
                'survey_date' => now()->subDays(rand(1, 2))->toDateString(),
                'notes' => 'Tiang aman, tarikan lurus',
                'photos' => [],
            ]);
        }

        // 6. Buat Dummy Data Survey SELESAI (Unfeasible)
        $tech = $technicians->random();
        $customer = Customer::create([
            'customer_code' => 'CUST-' . strtoupper($faker->bothify('???####')),
            'name' => 'Tidak Layak ' . $faker->firstName,
            'email' => $faker->unique()->safeEmail,
            'phone' => $faker->phoneNumber,
            'address' => $faker->address,
            'latitude' => $faker->latitude(-9, 6),
            'longitude' => $faker->longitude(95, 141),
            'package_id' => $packages->random()->id,
            'status' => 'survey',
            'notes' => 'Jauh dari tiang ODP',
        ]);

        Survey::create([
            'customer_id' => $customer->id,
            'odp_id' => $odp1->id,
            'surveyor_id' => $tech->id,
            'distance_meters' => 500,
            'port_available' => false,
            'feasibility' => 'not_feasible',
            'survey_date' => now()->subDays(1)->toDateString(),
            'notes' => 'Terlalu jauh, lebih dari 500 meter, redaman tinggi',
            'photos' => [],
        ]);

        // 7. Buat Dummy Data Installing (Sedang Dipasang)
        for ($i = 0; $i < 3; $i++) {
            $customer = Customer::create([
                'customer_code' => 'CUST-' . strtoupper($faker->bothify('???####')),
                'name' => 'Instalasi ' . $faker->firstName,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'address' => $faker->address,
                'area' => 'Area 1',
                'latitude' => $faker->latitude(-9, 6),
                'longitude' => $faker->longitude(95, 141),
                'package_id' => $packages->random()->id,
                'status' => 'installing',
                'notes' => 'Proses penarikan kabel ke rumah',
            ]);
            
            TechnicianSchedule::create([
                'ticket_id' => null,
                'technician_id' => $technicians->random()->id,
                'customer_id' => $customer->id,
                'scheduled_date' => now()->toDateString(),
                'scheduled_time' => sprintf('%02d:00', rand(9, 15)),
                'type' => 'installation',
                'status' => 'working',
                'notes' => 'Instalasi sedang berjalan',
            ]);
        }

        // 8. Buat Dummy Data Active (Sudah Aktif)
        for ($i = 0; $i < 15; $i++) {
            $customer = Customer::create([
                'customer_code' => 'CUST-' . strtoupper($faker->bothify('???####')),
                'name' => 'Aktif ' . $faker->firstName,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'address' => $faker->address,
                'area' => 'Area ' . rand(1, 3),
                'latitude' => $faker->latitude(-9, 6),
                'longitude' => $faker->longitude(95, 141),
                'package_id' => $packages->random()->id,
                'status' => 'active',
                'registration_date' => now()->subMonths(rand(1, 12))->toDateString(),
                'activation_date' => now()->subMonths(rand(1, 12))->addDays(rand(2, 5))->toDateString(),
                'notes' => 'Pelanggan aktif',
            ]);

            // Assign ONT
            \App\Models\Ont::create([
                'odp_id' => $odp1->id,
                'customer_id' => $customer->id,
                'serial_number' => 'ZTEG' . strtoupper($faker->bothify('######')),
                'mac_address' => $faker->macAddress,
                'brand' => 'ZTE',
                'model' => 'F609',
                'port_number' => rand(1, 8),
                'rx_power' => $faker->randomFloat(2, -25, -15),
                'tx_power' => $faker->randomFloat(2, 2, 4),
                'status' => 'active',
            ]);
        }

        // 9. Buat Dummy Data Suspended (Isolir)
        for ($i = 0; $i < 3; $i++) {
            Customer::create([
                'customer_code' => 'CUST-' . strtoupper($faker->bothify('???####')),
                'name' => 'Isolir ' . $faker->firstName,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'address' => $faker->address,
                'area' => 'Area 1',
                'latitude' => $faker->latitude(-9, 6),
                'longitude' => $faker->longitude(95, 141),
                'package_id' => $packages->random()->id,
                'status' => 'suspended',
                'registration_date' => now()->subMonths(5)->toDateString(),
                'activation_date' => now()->subMonths(5)->addDays(2)->toDateString(),
                'notes' => 'Isolir karena telat bayar 2 bulan',
            ]);
        }

        // 10. Buat Dummy Data Terminated (Berhenti)
        for ($i = 0; $i < 2; $i++) {
            Customer::create([
                'customer_code' => 'CUST-' . strtoupper($faker->bothify('???####')),
                'name' => 'Berhenti ' . $faker->firstName,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'address' => $faker->address,
                'area' => 'Area 2',
                'latitude' => $faker->latitude(-9, 6),
                'longitude' => $faker->longitude(95, 141),
                'package_id' => $packages->random()->id,
                'status' => 'terminated',
                'registration_date' => now()->subMonths(10)->toDateString(),
                'activation_date' => now()->subMonths(10)->addDays(3)->toDateString(),
                'notes' => 'Pindah rumah',
            ]);
        }

        $this->command->info('Dummy Pelanggan (Booking, Survey, Installing, Active, Suspended, Terminated) berhasil ditambahkan secara lengkap!');
    }
}

