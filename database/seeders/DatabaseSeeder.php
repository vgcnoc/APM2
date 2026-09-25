<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Ont;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Users ──────────────────────────────────────────────
        $users = [
            ['name' => 'Super Admin', 'email' => 'admin@isp.local', 'role' => 'admin'],
            ['name' => 'Customer Service', 'email' => 'cs@isp.local', 'role' => 'cs'],
            ['name' => 'Budi Teknisi', 'email' => 'teknisi1@isp.local', 'role' => 'teknisi'],
            ['name' => 'Andi Teknisi', 'email' => 'teknisi2@isp.local', 'role' => 'teknisi'],
            ['name' => 'Sari Sales', 'email' => 'sales@isp.local', 'role' => 'sales'],
            ['name' => 'NOC Operator', 'email' => 'noc@isp.local', 'role' => 'noc'],
        ];

        foreach ($users as $userData) {
            User::create(array_merge($userData, [
                'password' => Hash::make('password'),
                'is_active' => true,
            ]));
        }

        // ── Internet Packages ──────────────────────────────────
        $packages = [
            ['name' => 'Starter 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'description' => 'Cocok untuk browsing & sosial media'],
            ['name' => 'Home 20 Mbps', 'speed_mbps' => 20, 'price' => 250000, 'description' => 'Ideal untuk streaming & gaming ringan'],
            ['name' => 'Pro 50 Mbps', 'speed_mbps' => 50, 'price' => 400000, 'description' => 'Untuk keluarga & work from home'],
            ['name' => 'Business 100 Mbps', 'speed_mbps' => 100, 'price' => 750000, 'description' => 'Solusi bisnis & UMKM'],
            ['name' => 'Enterprise 200 Mbps', 'speed_mbps' => 200, 'price' => 1500000, 'description' => 'Dedicated enterprise solution'],
        ];

        foreach ($packages as $pkg) {
            InternetPackage::create($pkg);
        }

        // ── OLT ────────────────────────────────────────────────
        $olt1 = Olt::create([
            'name' => 'OLT-PUSAT-01',
            'hostname' => 'olt-pusat-01.isp.local',
            'ip_address' => '192.168.1.1',
            'brand' => 'ZTE',
            'model' => 'ZXA10 C320',
            'total_pon_ports' => 16,
            'location' => 'Data Center Utama, Jl. Raya No. 1',
            'latitude' => -6.2088000,
            'longitude' => 106.8456000,
            'status' => 'active',
        ]);

        $olt2 = Olt::create([
            'name' => 'OLT-CABANG-02',
            'hostname' => 'olt-cabang-02.isp.local',
            'ip_address' => '192.168.2.1',
            'brand' => 'Huawei',
            'model' => 'MA5608T',
            'total_pon_ports' => 8,
            'location' => 'POP Cabang Selatan, Jl. Merdeka No. 50',
            'latitude' => -6.2500000,
            'longitude' => 106.8300000,
            'status' => 'active',
        ]);

        // ── ODC ────────────────────────────────────────────────
        $odc1 = Odc::create([
            'olt_id' => $olt1->id,
            'name' => 'ODC-KBN-01',
            'location' => 'Jl. Kebun Jeruk Raya',
            'latitude' => -6.1900000,
            'longitude' => 106.7700000,
            'capacity' => 144,
            'status' => 'active',
        ]);

        $odc2 = Odc::create([
            'olt_id' => $olt1->id,
            'name' => 'ODC-CMP-01',
            'location' => 'Jl. Cempaka Putih',
            'latitude' => -6.1750000,
            'longitude' => 106.8700000,
            'capacity' => 96,
            'status' => 'active',
        ]);

        $odc3 = Odc::create([
            'olt_id' => $olt2->id,
            'name' => 'ODC-SLT-01',
            'location' => 'Jl. Selatan Baru',
            'latitude' => -6.2600000,
            'longitude' => 106.8400000,
            'capacity' => 96,
            'status' => 'active',
        ]);

        // ── ODP ────────────────────────────────────────────────
        $odps = [];

        $odpData = [
            ['odc_id' => $odc1->id, 'name' => 'ODP-KBN-01-A', 'lat' => -6.1910, 'lng' => 106.7710, 'ports' => 8],
            ['odc_id' => $odc1->id, 'name' => 'ODP-KBN-01-B', 'lat' => -6.1920, 'lng' => 106.7720, 'ports' => 16],
            ['odc_id' => $odc1->id, 'name' => 'ODP-KBN-01-C', 'lat' => -6.1930, 'lng' => 106.7730, 'ports' => 8],
            ['odc_id' => $odc2->id, 'name' => 'ODP-CMP-01-A', 'lat' => -6.1760, 'lng' => 106.8710, 'ports' => 16],
            ['odc_id' => $odc2->id, 'name' => 'ODP-CMP-01-B', 'lat' => -6.1770, 'lng' => 106.8720, 'ports' => 8],
            ['odc_id' => $odc3->id, 'name' => 'ODP-SLT-01-A', 'lat' => -6.2610, 'lng' => 106.8410, 'ports' => 16],
            ['odc_id' => $odc3->id, 'name' => 'ODP-SLT-01-B', 'lat' => -6.2620, 'lng' => 106.8420, 'ports' => 8],
        ];

        foreach ($odpData as $od) {
            $odps[] = Odp::create([
                'odc_id' => $od['odc_id'],
                'name' => $od['name'],
                'latitude' => $od['lat'],
                'longitude' => $od['lng'],
                'total_ports' => $od['ports'],
                'used_ports' => 0,
                'status' => 'active',
            ]);
        }

        // ── Customers + ONTs ───────────────────────────────────
        $customerData = [
            ['name' => 'Ahmad Fauzi', 'phone' => '081234567001', 'address' => 'Jl. Kebun Jeruk No. 10', 'status' => 'active', 'pkg' => 2],
            ['name' => 'Siti Nurhaliza', 'phone' => '081234567002', 'address' => 'Jl. Kebun Jeruk No. 15', 'status' => 'active', 'pkg' => 3],
            ['name' => 'Budi Santoso', 'phone' => '081234567003', 'address' => 'Jl. Cempaka No. 5', 'status' => 'active', 'pkg' => 1],
            ['name' => 'Dewi Lestari', 'phone' => '081234567004', 'address' => 'Jl. Cempaka No. 8', 'status' => 'active', 'pkg' => 4],
            ['name' => 'Rudi Hartono', 'phone' => '081234567005', 'address' => 'Jl. Selatan No. 20', 'status' => 'active', 'pkg' => 2],
            ['name' => 'Rina Wijaya', 'phone' => '081234567006', 'address' => 'Jl. Selatan No. 25', 'status' => 'survey', 'pkg' => 1],
            ['name' => 'Eko Prasetyo', 'phone' => '081234567007', 'address' => 'Jl. Merdeka No. 100', 'status' => 'booking', 'pkg' => null],
            ['name' => 'Maya Sari', 'phone' => '081234567008', 'address' => 'Jl. Merdeka No. 102', 'status' => 'booking', 'pkg' => null],
            ['name' => 'Hendra Gunawan', 'phone' => '081234567009', 'address' => 'Jl. Kebun Jeruk No. 30', 'status' => 'installing', 'pkg' => 3],
            ['name' => 'Lisa Permata', 'phone' => '081234567010', 'address' => 'Jl. Cempaka No. 12', 'status' => 'suspended', 'pkg' => 2],
        ];

        $ontIndex = 1;
        foreach ($customerData as $cd) {
            $customer = Customer::create([
                'name' => $cd['name'],
                'phone' => $cd['phone'],
                'address' => $cd['address'],
                'status' => $cd['status'],
                'package_id' => $cd['pkg'],
                'registration_date' => now()->subDays(rand(10, 90))->toDateString(),
                'activation_date' => $cd['status'] === 'active' ? now()->subDays(rand(1, 30))->toDateString() : null,
            ]);

            // Hanya pelanggan aktif yang punya ONT
            if ($cd['status'] === 'active') {
                $odpIndex = min($ontIndex - 1, count($odps) - 1);
                $odp = $odps[$odpIndex % count($odps)];

                Ont::create([
                    'odp_id' => $odp->id,
                    'customer_id' => $customer->id,
                    'serial_number' => 'ZTEG' . str_pad($ontIndex, 8, '0', STR_PAD_LEFT),
                    'mac_address' => sprintf('AA:BB:CC:DD:%02X:%02X', rand(0, 255), $ontIndex),
                    'brand' => $ontIndex % 2 === 0 ? 'ZTE' : 'Huawei',
                    'model' => $ontIndex % 2 === 0 ? 'F670L' : 'HG8245H5',
                    'port_number' => $odp->used_ports + 1,
                    'rx_power' => -1 * (rand(1800, 2600) / 100), // -18.00 ~ -26.00 dBm
                    'tx_power' => rand(100, 300) / 100,           //   1.00 ~  3.00 dBm
                    'status' => 'active',
                ]);

                $odp->increment('used_ports');
                $ontIndex++;
            }
        }

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('   Login: admin@isp.local / password');
    }
}
