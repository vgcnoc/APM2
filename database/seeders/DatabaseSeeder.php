<?php

namespace Database\Seeders;

use App\Models\InternetPackage;
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

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('   Login: admin@isp.local / password');
    }
}
