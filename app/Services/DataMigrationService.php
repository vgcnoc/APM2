<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Area;
use App\Models\InternetPackage;
use App\Models\User;
use App\Models\Olt;
use App\Models\OltPon;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\OdpPort;
use App\Models\Ont;
use App\Models\Survey;
use App\Models\TechnicianSchedule;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DataMigrationService
{
    /**
     * Master schema definition per category
     */
    public static function getCategoryDefinitions(): array
    {
        return [
            'customers' => [
                'title' => 'Data Pelanggan',
                'description' => 'Migrasi biodata pelanggan, status paket, alamat, dan profil billing.',
                'target_table' => 'customers',
                'id_column' => 'customer_code',
                'headers' => [
                    'kode_pelanggan' => ['label' => 'Kode Pelanggan (CUS-XXXXXX)', 'required' => false, 'db' => 'customer_code'],
                    'nama_lengkap' => ['label' => 'Nama Lengkap Pelanggan', 'required' => true, 'db' => 'name'],
                    'nomor_wa' => ['label' => 'Nomor WhatsApp / HP', 'required' => true, 'db' => 'phone'],
                    'email' => ['label' => 'Alamat Email', 'required' => false, 'db' => 'email'],
                    'nama_area' => ['label' => 'Nama Area / Wilayah', 'required' => true, 'db' => 'area'],
                    'alamat_jalan' => ['label' => 'Detail Alamat / Jalan', 'required' => true, 'db' => 'address_detail'],
                    'rt_rw' => ['label' => 'RT / RW', 'required' => false, 'db' => 'rt_rw'],
                    'kelurahan_desa' => ['label' => 'Desa / Kelurahan', 'required' => false, 'db' => 'village'],
                    'kecamatan' => ['label' => 'Kecamatan', 'required' => false, 'db' => 'district'],
                    'nama_paket' => ['label' => 'Nama Paket Internet', 'required' => false, 'db' => 'package_name'],
                    'tarif_bulanan' => ['label' => 'Tarif Bulanan (Rp)', 'required' => true, 'db' => 'base_amount'],
                    'biaya_pasang' => ['label' => 'Biaya Pasang Baru (Rp)', 'required' => false, 'db' => 'installation_fee'],
                    'status_layanan' => ['label' => 'Status Layanan (berbayar/gratis)', 'required' => false, 'db' => 'service_status'],
                    'jenis_billing' => ['label' => 'Jenis Billing (prabayar/pascabayar)', 'required' => false, 'db' => 'billing_type'],
                    'status_pelanggan' => ['label' => 'Status Pelanggan (active/booking/survey/installing/suspended/terminated)', 'required' => true, 'db' => 'status'],
                    'tgl_registrasi' => ['label' => 'Tanggal Registrasi (YYYY-MM-DD)', 'required' => false, 'db' => 'registration_date'],
                    'tgl_aktivasi' => ['label' => 'Tanggal Aktivasi (YYYY-MM-DD)', 'required' => false, 'db' => 'activation_date'],
                    'username_sales' => ['label' => 'Username / Email Sales', 'required' => false, 'db' => 'sales_ref'],
                    'latitude' => ['label' => 'Latitude Koordinat', 'required' => false, 'db' => 'latitude'],
                    'longitude' => ['label' => 'Longitude Koordinat', 'required' => false, 'db' => 'longitude'],
                    'catatan' => ['label' => 'Catatan Tambahan', 'required' => false, 'db' => 'notes'],
                ],
                'sample' => [
                    [
                        'kode_pelanggan' => 'CUS-001001',
                        'nama_lengkap' => 'Ahmad Subardjo',
                        'nomor_wa' => '081234567890',
                        'email' => 'ahmad@example.com',
                        'nama_area' => 'Area Timur',
                        'alamat_jalan' => 'Jl. Mawar No. 12',
                        'rt_rw' => '02/05',
                        'kelurahan_desa' => 'Sukasari',
                        'kecamatan' => 'Cimahi',
                        'nama_paket' => 'Home 20 Mbps',
                        'tarif_bulanan' => 175000,
                        'biaya_pasang' => 0,
                        'status_layanan' => 'berbayar',
                        'jenis_billing' => 'prabayar',
                        'status_pelanggan' => 'active',
                        'tgl_registrasi' => '2026-01-10',
                        'tgl_aktivasi' => '2026-01-12',
                        'username_sales' => 'sales1',
                        'latitude' => '-6.891234',
                        'longitude' => '107.567890',
                        'catatan' => 'Migrasi dari billing lama',
                    ],
                ]
            ],

            'network' => [
                'title' => 'Data Jaringan (OLT, ODC, ODP, Port)',
                'description' => 'Migrasi struktur topologi jaringan FTTH: OLT, ODC, ODP, dan Port ke Pelanggan.',
                'target_table' => 'odps',
                'id_column' => 'nama_odp',
                'headers' => [
                    'nama_olt' => ['label' => 'Nama OLT / Server Induk', 'required' => true, 'db' => 'olt_name'],
                    'ip_olt' => ['label' => 'IP Address OLT', 'required' => false, 'db' => 'olt_ip'],
                    'pon_port_olt' => ['label' => 'Nomor PON Port OLT (1..16)', 'required' => false, 'db' => 'pon_port'],
                    'nama_odc' => ['label' => 'Nama ODC', 'required' => true, 'db' => 'odc_name'],
                    'splitter_odc' => ['label' => 'Splitter Ratio ODC (misal 1:4)', 'required' => false, 'db' => 'splitter_ratio'],
                    'nama_odp' => ['label' => 'Nama ODP', 'required' => true, 'db' => 'odp_name'],
                    'total_port_odp' => ['label' => 'Total Port ODP (8 / 16)', 'required' => true, 'db' => 'total_ports'],
                    'nomor_port' => ['label' => 'Nomor Port Fisik (1..N)', 'required' => true, 'db' => 'port_number'],
                    'status_port' => ['label' => 'Status Port (available/used/reserved/fault/stop)', 'required' => false, 'db' => 'port_status'],
                    'kode_pelanggan' => ['label' => 'Kode Pelanggan (Jika Terhubung)', 'required' => false, 'db' => 'customer_code'],
                    'sn_ont' => ['label' => 'Serial Number ONT', 'required' => false, 'db' => 'serial_number'],
                    'mac_ont' => ['label' => 'MAC Address ONT', 'required' => false, 'db' => 'mac_address'],
                    'vlan_id' => ['label' => 'VLAN ID PPPoE', 'required' => false, 'db' => 'vlan_id'],
                    'nama_area' => ['label' => 'Nama Area Wilayah ODP', 'required' => true, 'db' => 'area_name'],
                    'latitude' => ['label' => 'Latitude ODP', 'required' => false, 'db' => 'latitude'],
                    'longitude' => ['label' => 'Longitude ODP', 'required' => false, 'db' => 'longitude'],
                ],
                'sample' => [
                    [
                        'nama_olt' => 'OLT-ZTE-PUSAT',
                        'ip_olt' => '192.168.10.1',
                        'pon_port_olt' => 1,
                        'nama_odc' => 'ODC-TMR-01',
                        'splitter_odc' => '1:4',
                        'nama_odp' => 'ODP-TMR-01-A',
                        'total_port_odp' => 8,
                        'nomor_port' => 1,
                        'status_port' => 'used',
                        'kode_pelanggan' => 'CUS-001001',
                        'sn_ont' => 'ZTEG98765432',
                        'mac_ont' => 'CC:2D:21:4A:88:01',
                        'vlan_id' => '100',
                        'nama_area' => 'Area Timur',
                        'latitude' => '-6.892000',
                        'longitude' => '107.568000',
                    ],
                ]
            ],

            'booking_survey' => [
                'title' => 'Data Booking & Survey',
                'description' => 'Migrasi data calon pelanggan, hasil survey kelayakan ODP, dan petugas surveyor.',
                'target_table' => 'surveys',
                'id_column' => 'kode_pelanggan',
                'headers' => [
                    'kode_pelanggan' => ['label' => 'Kode Pelanggan', 'required' => true, 'db' => 'customer_code'],
                    'email_petugas_survey' => ['label' => 'Email / Username Petugas Survey', 'required' => true, 'db' => 'surveyor_email'],
                    'nama_odp' => ['label' => 'Nama ODP Terpilih', 'required' => false, 'db' => 'odp_name'],
                    'nomor_port' => ['label' => 'Nomor Port ODP', 'required' => false, 'db' => 'port_number'],
                    'jarak_meter' => ['label' => 'Jarak Penarikan Kabel (meter)', 'required' => false, 'db' => 'distance_meters'],
                    'hasil_kelayakan' => ['label' => 'Hasil Kelayakan (feasible/not_feasible)', 'required' => true, 'db' => 'feasibility'],
                    'redaman_sinyal' => ['label' => 'Redaman Sinyal (dBm)', 'required' => false, 'db' => 'signal_loss'],
                    'tgl_survey' => ['label' => 'Tanggal Survey (YYYY-MM-DD)', 'required' => false, 'db' => 'survey_date'],
                    'catatan_survey' => ['label' => 'Catatan Hasil Survey', 'required' => false, 'db' => 'notes'],
                ],
                'sample' => [
                    [
                        'kode_pelanggan' => 'CUS-001001',
                        'email_petugas_survey' => 'teknisi1@example.com',
                        'nama_odp' => 'ODP-TMR-01-A',
                        'nomor_port' => 1,
                        'jarak_meter' => 85,
                        'hasil_kelayakan' => 'feasible',
                        'redaman_sinyal' => -19.5,
                        'tgl_survey' => '2026-01-11',
                        'catatan_survey' => 'Jalur kabel aman lewat tiang PLN',
                    ]
                ]
            ],

            'installation_activation' => [
                'title' => 'Data Instalasi & Aktivasi Historis',
                'description' => 'Migrasi perangkat ONT, redaman, akun PPPoE, VLAN, dan riwayat aktivasi layanan.',
                'target_table' => 'onts',
                'id_column' => 'sn_ont',
                'headers' => [
                    'kode_pelanggan' => ['label' => 'Kode Pelanggan', 'required' => true, 'db' => 'customer_code'],
                    'sn_ont' => ['label' => 'Serial Number Modem ONT', 'required' => true, 'db' => 'serial_number'],
                    'mac_address' => ['label' => 'MAC Address Modem', 'required' => false, 'db' => 'mac_address'],
                    'merk_ont' => ['label' => 'Merk Modem (ZTE/Huawei/dll)', 'required' => false, 'db' => 'brand'],
                    'tipe_model' => ['label' => 'Tipe Model Modem', 'required' => false, 'db' => 'model'],
                    'nama_odp' => ['label' => 'Nama ODP Terpasang', 'required' => true, 'db' => 'odp_name'],
                    'nomor_port_odp' => ['label' => 'Nomor Port ODP Terpasang', 'required' => true, 'db' => 'port_number'],
                    'redaman_rx' => ['label' => 'Redaman Optik Rx (dBm, misal: -21.50)', 'required' => true, 'db' => 'rx_power'],
                    'username_pppoe' => ['label' => 'Username PPPoE', 'required' => false, 'db' => 'pppoe_user'],
                    'password_pppoe' => ['label' => 'Password PPPoE', 'required' => false, 'db' => 'pppoe_password'],
                    'vlan_id' => ['label' => 'VLAN ID PPPoE', 'required' => false, 'db' => 'vlan_id'],
                    'mode_akses' => ['label' => 'Mode Akses (PPPOE/IPOE/BRIDGE)', 'required' => false, 'db' => 'access_mode'],
                    'tgl_aktivasi' => ['label' => 'Tanggal Aktivasi (YYYY-MM-DD)', 'required' => true, 'db' => 'activation_date'],
                    'lulus_audit_qc' => ['label' => 'Status Audit QC (1 = Lulus, 0 = Belum)', 'required' => false, 'db' => 'is_audited'],
                    'jam_pasang_mulai' => ['label' => 'Jam Pasang Mulai (HH:MM)', 'required' => false, 'db' => 'start_time'],
                    'jam_pasang_selesai' => ['label' => 'Jam Pasang Selesai (HH:MM)', 'required' => false, 'db' => 'end_time'],
                ],
                'sample' => [
                    [
                        'kode_pelanggan' => 'CUS-001001',
                        'sn_ont' => 'ZTEG98765432',
                        'mac_address' => 'CC:2D:21:4A:88:01',
                        'merk_ont' => 'ZTE',
                        'tipe_model' => 'F609',
                        'nama_odp' => 'ODP-TMR-01-A',
                        'nomor_port_odp' => 1,
                        'redaman_rx' => -21.40,
                        'username_pppoe' => 'ahmad_subardjo',
                        'password_pppoe' => 'subardjo123',
                        'vlan_id' => '100',
                        'mode_akses' => 'PPPOE',
                        'tgl_aktivasi' => '2026-01-12',
                        'lulus_audit_qc' => 1,
                        'jam_pasang_mulai' => '09:00',
                        'jam_pasang_selesai' => '11:30',
                    ]
                ]
            ],

            'invoices' => [
                'title' => 'Data Tagihan / Invoice Historis',
                'description' => 'Migrasi invoice tagihan bulanan pelanggan yang lunas maupun belum lunas.',
                'target_table' => 'invoices',
                'id_column' => 'nomor_invoice',
                'headers' => [
                    'nomor_invoice' => ['label' => 'Nomor Invoice (INV-YYYYMM-XXXX)', 'required' => false, 'db' => 'invoice_number'],
                    'kode_pelanggan' => ['label' => 'Kode Pelanggan Terkait', 'required' => true, 'db' => 'customer_code'],
                    'bulan_periode' => ['label' => 'Bulan Periode (1 s/d 12)', 'required' => true, 'db' => 'period_month'],
                    'tahun_periode' => ['label' => 'Tahun Periode (misal: 2026)', 'required' => true, 'db' => 'period_year'],
                    'total_tagihan' => ['label' => 'Total Nominal Tagihan (Rp)', 'required' => true, 'db' => 'amount'],
                    'tgl_terbit' => ['label' => 'Tanggal Invoice Terbit (YYYY-MM-DD)', 'required' => true, 'db' => 'issued_date'],
                    'tgl_jatuh_tempo' => ['label' => 'Tanggal Jatuh Tempo (YYYY-MM-DD)', 'required' => true, 'db' => 'due_date'],
                    'status_invoice' => ['label' => 'Status Tagihan (unpaid/paid/partial/cancelled)', 'required' => true, 'db' => 'status'],
                    'jenis_tagihan' => ['label' => 'Jenis Tagihan (prabayar/pascabayar/prorata)', 'required' => false, 'db' => 'billing_type'],
                    'catatan_invoice' => ['label' => 'Catatan / Keterangan Tagihan', 'required' => false, 'db' => 'notes'],
                ],
                'sample' => [
                    [
                        'nomor_invoice' => 'INV-202601-0012',
                        'kode_pelanggan' => 'CUS-001001',
                        'bulan_periode' => 1,
                        'tahun_periode' => 2026,
                        'total_tagihan' => 175000,
                        'tgl_terbit' => '2026-01-12',
                        'tgl_jatuh_tempo' => '2026-01-20',
                        'status_invoice' => 'paid',
                        'jenis_tagihan' => 'prabayar',
                        'catatan_invoice' => 'Tagihan Periode Januari 2026',
                    ]
                ]
            ],

            'payments' => [
                'title' => 'Data Pembayaran Historis',
                'description' => 'Migrasi transaksi pembayaran pelanggan untuk rekonsiliasi invoice.',
                'target_table' => 'payments',
                'id_column' => 'nomor_referensi',
                'headers' => [
                    'nomor_invoice' => ['label' => 'Nomor Invoice yang Dibayar', 'required' => true, 'db' => 'invoice_number'],
                    'nominal_bayar' => ['label' => 'Nominal Pembayaran (Rp)', 'required' => true, 'db' => 'amount'],
                    'tgl_bayar' => ['label' => 'Tanggal Pembayaran (YYYY-MM-DD)', 'required' => true, 'db' => 'payment_date'],
                    'metode_bayar' => ['label' => 'Metode Bayar (cash/transfer/ewallet/auto_debit)', 'required' => true, 'db' => 'payment_method'],
                    'nomor_referensi' => ['label' => 'Nomor Bukti / Referensi Pembayaran', 'required' => false, 'db' => 'reference_number'],
                    'email_penerima' => ['label' => 'Email / Username Kasir Penerima', 'required' => false, 'db' => 'collector_email'],
                    'catatan' => ['label' => 'Catatan Pembayaran', 'required' => false, 'db' => 'notes'],
                ],
                'sample' => [
                    [
                        'nomor_invoice' => 'INV-202601-0012',
                        'nominal_bayar' => 175000,
                        'tgl_bayar' => '2026-01-15',
                        'metode_bayar' => 'transfer',
                        'nomor_referensi' => 'TRF-BCA-887192',
                        'email_penerima' => 'finance@example.com',
                        'catatan' => 'Lunas via Transfer BCA',
                    ]
                ]
            ],
        ];
    }

    /**
     * Generate CSV content with UTF-8 BOM
     */
    public function generateTemplateCsv(string $category): string
    {
        $defs = self::getCategoryDefinitions();
        if (!isset($defs[$category])) {
            throw new \InvalidArgumentException("Kategori {$category} tidak valid.");
        }

        $def = $defs[$category];
        $headers = array_keys($def['headers']);
        
        $output = fopen('php://memory', 'r+');
        // Add UTF-8 BOM so Excel opens it with correct encoding
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        // Add Excel separator directive so Windows Excel immediately splits columns by comma
        fwrite($output, "sep=,\r\n");

        // Write header row
        fputcsv($output, $headers, ',');

        // Write sample data rows
        foreach ($def['sample'] as $row) {
            $line = [];
            foreach ($headers as $h) {
                $line[] = $row[$h] ?? '';
            }
            fputcsv($output, $line, ',');
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Parse raw CSV string or uploaded file into associative array
     */
    public function parseCsv(string $content): array
    {
        // Strip BOM if present
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
        if (empty($lines)) return [];

        // Check if first line specifies Excel separator directive "sep=," or "sep=;"
        if (preg_match('/^sep=([;,|\t])/i', trim($lines[0]), $m)) {
            $delimiter = $m[1];
            array_shift($lines);
        } else {
            // Detect delimiter: check first line
            $firstLine = $lines[0] ?? '';
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        }

        $data = [];
        $headers = null;

        foreach ($lines as $lineIndex => $line) {
            $trimmed = trim($line);
            if ($trimmed === '') continue;

            $row = str_getcsv($trimmed, $delimiter);
            if (!$headers) {
                $headers = array_map(function ($h) {
                    return trim(strtolower(str_replace([' ', '-', '.'], '_', $h)));
                }, $row);
                continue;
            }

            if (count($row) === count($headers)) {
                $combined = array_combine($headers, array_map('trim', $row));
                $data[] = $combined;
            } elseif (count($row) > 1) {
                // Pad or slice
                $padded = array_pad($row, count($headers), '');
                $data[] = array_combine($headers, array_slice($padded, 0, count($headers)));
            }
        }

        return $data;
    }

    /**
     * Dry Run Validator
     */
    public function dryRun(string $category, array $rows, array $options = []): array
    {
        $defs = self::getCategoryDefinitions();
        if (!isset($defs[$category])) {
            throw new \InvalidArgumentException("Kategori {$category} tidak ditemukan.");
        }

        $results = [];
        $totalValid = 0;
        $totalWarnings = 0;
        $totalErrors = 0;

        // Preload caches for fast lookups
        $areasCache = Area::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id])->toArray();
        $packagesCache = InternetPackage::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id])->toArray();
        $usersCache = User::pluck('id', 'email')->mapWithKeys(fn($id, $email) => [strtolower($email) => $id])->toArray();
        $userNamesCache = User::pluck('id', 'name')->mapWithKeys(fn($id, $name) => [strtolower($name) => $id])->toArray();
        $customersCache = Customer::pluck('id', 'customer_code')->toArray();
        $odpsCache = Odp::with('area')->get()->keyBy(fn($o) => strtolower($o->name));
        $invoicesCache = Invoice::pluck('id', 'invoice_number')->toArray();

        foreach ($rows as $index => $row) {
            $rowNum = $index + 1;
            $rowErrors = [];
            $rowWarnings = [];
            $mapped = [];

            switch ($category) {
                case 'customers':
                    $this->validateCustomerRow($row, $rowErrors, $rowWarnings, $mapped, $areasCache, $packagesCache, $usersCache, $userNamesCache, $customersCache, $options);
                    break;
                case 'network':
                    $this->validateNetworkRow($row, $rowErrors, $rowWarnings, $mapped, $areasCache, $customersCache, $odpsCache, $options);
                    break;
                case 'booking_survey':
                    $this->validateSurveyRow($row, $rowErrors, $rowWarnings, $mapped, $customersCache, $usersCache, $odpsCache);
                    break;
                case 'installation_activation':
                    $this->validateInstallationRow($row, $rowErrors, $rowWarnings, $mapped, $customersCache, $odpsCache);
                    break;
                case 'invoices':
                    $this->validateInvoiceRow($row, $rowErrors, $rowWarnings, $mapped, $customersCache, $invoicesCache);
                    break;
                case 'payments':
                    $this->validatePaymentRow($row, $rowErrors, $rowWarnings, $mapped, $invoicesCache, $usersCache);
                    break;
            }

            $status = count($rowErrors) > 0 ? 'error' : (count($rowWarnings) > 0 ? 'warning' : 'valid');
            if ($status === 'valid') $totalValid++;
            elseif ($status === 'warning') $totalWarnings++;
            else $totalErrors++;

            $results[] = [
                'row_index' => $rowNum,
                'status' => $status,
                'raw' => $row,
                'mapped' => $mapped,
                'errors' => $rowErrors,
                'warnings' => $rowWarnings,
            ];
        }

        return [
            'summary' => [
                'total' => count($rows),
                'valid' => $totalValid,
                'warnings' => $totalWarnings,
                'errors' => $totalErrors,
            ],
            'rows' => $results,
        ];
    }

    /**
     * Execute Import Transaction
     */
    public function execute(string $category, array $rows, array $options = []): array
    {
        // 1. Dry run first to verify consistency
        $dryRun = $this->dryRun($category, $rows, $options);
        if ($dryRun['summary']['errors'] > 0 && empty($options['skip_errors'])) {
            return [
                'success' => false,
                'message' => "Impor dibatalkan: ditemukan {$dryRun['summary']['errors']} baris dengan kesalahan. Perbaiki data sebelum melanjutkan.",
                'summary' => $dryRun['summary'],
                'errors' => array_filter($dryRun['rows'], fn($r) => $r['status'] === 'error')
            ];
        }

        $duplicateMode = $options['duplicate_mode'] ?? 'skip'; // skip | update
        $autoCreateArea = $options['auto_create_area'] ?? true;
        $batchId = 'BATCH-' . strtoupper($category) . '-' . date('YmdHis');

        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $logs = [];

        DB::beginTransaction();
        try {
            foreach ($dryRun['rows'] as $r) {
                if ($r['status'] === 'error') {
                    $skippedCount++;
                    continue;
                }

                $data = $r['mapped'];

                switch ($category) {
                    case 'customers':
                        $res = $this->importCustomerItem($data, $duplicateMode, $autoCreateArea);
                        break;
                    case 'network':
                        $res = $this->importNetworkItem($data, $duplicateMode, $autoCreateArea);
                        break;
                    case 'booking_survey':
                        $res = $this->importSurveyItem($data, $duplicateMode);
                        break;
                    case 'installation_activation':
                        $res = $this->importInstallationItem($data, $duplicateMode);
                        break;
                    case 'invoices':
                        $res = $this->importInvoiceItem($data, $duplicateMode);
                        break;
                    case 'payments':
                        $res = $this->importPaymentItem($data, $duplicateMode);
                        break;
                    default:
                        $res = 'skip';
                }

                if ($res === 'inserted') $insertedCount++;
                elseif ($res === 'updated') $updatedCount++;
                else $skippedCount++;
            }

            // Record to Audit Log
            AuditLog::createLog(
                "Data Migration ({$category})",
                new Customer(), // entity placeholder
                null,
                'imported',
                "Batch {$batchId}: Sukses {$insertedCount} baru, {$updatedCount} diperbarui, {$skippedCount} dilewati."
            );

            DB::commit();

            return [
                'success' => true,
                'batch_id' => $batchId,
                'message' => "Migrasi data {$category} berhasil diselesaikan.",
                'summary' => [
                    'total' => count($rows),
                    'inserted' => $insertedCount,
                    'updated' => $updatedCount,
                    'skipped' => $skippedCount,
                ],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("[DataMigrationService] Execute error on {$category}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menyimpan ke database: ' . $e->getMessage(),
            ];
        }
    }

    // ── Row Validators ──────────────────────────────────────────────

    protected function validateCustomerRow($row, &$errors, &$warnings, &$mapped, $areasCache, $packagesCache, $usersCache, $userNamesCache, $customersCache, $options)
    {
        $name = $row['nama_lengkap'] ?? $row['nama'] ?? '';
        $phone = $row['nomor_wa'] ?? $row['phone'] ?? $row['no_wa'] ?? '';
        $areaName = $row['nama_area'] ?? $row['area'] ?? '';
        $customerCode = $row['kode_pelanggan'] ?? $row['customer_code'] ?? '';

        if (empty($name)) $errors[] = 'Nama Lengkap wajib diisi.';
        if (empty($phone)) $errors[] = 'Nomor WA / Telepon wajib diisi.';
        if (empty($areaName)) $errors[] = 'Nama Area / Wilayah wajib diisi.';

        // Check duplicate
        $isExisting = false;
        if (!empty($customerCode) && isset($customersCache[$customerCode])) {
            $isExisting = true;
            if (($options['duplicate_mode'] ?? 'skip') === 'update') {
                $warnings[] = "Kode pelanggan '{$customerCode}' sudah ada; data akan diperbarui.";
            } else {
                $warnings[] = "Kode pelanggan '{$customerCode}' sudah terdaftar; baris ini akan dilewati.";
            }
        }

        // Area check
        $areaId = $areasCache[strtolower($areaName)] ?? null;
        if (!$areaId) {
            $warnings[] = "Area '{$areaName}' belum ada di master data; sistem akan otomatis membuatnya.";
        }

        // Package check
        $packageName = $row['nama_paket'] ?? $row['paket'] ?? '';
        $packageId = null;
        if (!empty($packageName)) {
            $packageId = $packagesCache[strtolower($packageName)] ?? null;
            if (!$packageId) {
                $warnings[] = "Paket '{$packageName}' tidak ditemukan di master paket. Pelanggan diimpor tanpa kaitan paket.";
            }
        }

        // Sales check
        $salesRef = $row['username_sales'] ?? $row['sales'] ?? '';
        $salesId = null;
        if (!empty($salesRef)) {
            $salesId = $usersCache[strtolower($salesRef)] ?? ($userNamesCache[strtolower($salesRef)] ?? null);
            if (!$salesId) {
                $warnings[] = "Petugas sales '{$salesRef}' tidak ditemukan. Sales dikosongkan.";
            }
        }

        // Address compilation
        $street = $row['alamat_jalan'] ?? $row['address'] ?? '';
        $rtRw = $row['rt_rw'] ?? '';
        $village = $row['kelurahan_desa'] ?? $row['desa'] ?? '';
        $district = $row['kecamatan'] ?? '';

        $addressParts = array_filter([
            $street,
            $rtRw ? 'RT/RW: ' . $rtRw : null,
            $village ? 'Kel. ' . $village : null,
            $district ? 'Kec. ' . $district : null,
        ]);
        $address = implode(', ', $addressParts) ?: '-';

        $status = strtolower($row['status_pelanggan'] ?? $row['status'] ?? 'active');
        $validStatuses = ['booking', 'survey', 'installing', 'active', 'suspended', 'terminated', 'canceled'];
        if (!in_array($status, $validStatuses)) {
            $warnings[] = "Status '{$status}' tidak valid, diubah default ke 'active'.";
            $status = 'active';
        }

        $serviceStatus = strtolower($row['status_layanan'] ?? 'berbayar');
        if (!in_array($serviceStatus, ['berbayar', 'gratis'])) $serviceStatus = 'berbayar';

        $baseAmount = (float) ($row['tarif_bulanan'] ?? $row['base_amount'] ?? 0);
        if ($serviceStatus === 'gratis') $baseAmount = 0;

        $mapped = [
            'customer_code' => $customerCode ?: null,
            'name' => $name,
            'phone' => $phone,
            'email' => $row['email'] ?? null,
            'area' => $areaName,
            'area_id' => $areaId,
            'address' => $address,
            'package_id' => $packageId,
            'base_amount' => $baseAmount,
            'installation_fee' => (float) ($row['biaya_pasang'] ?? 0),
            'service_status' => $serviceStatus,
            'billing_type' => in_array(strtolower($row['jenis_billing'] ?? ''), ['prabayar', 'pascabayar']) ? strtolower($row['jenis_billing']) : 'prabayar',
            'status' => $status,
            'registration_date' => $row['tgl_registrasi'] ?? now()->toDateString(),
            'activation_date' => ($status === 'active') ? ($row['tgl_aktivasi'] ?? now()->toDateString()) : ($row['tgl_aktivasi'] ?? null),
            'sales_id' => $salesId,
            'latitude' => !empty($row['latitude']) ? (float)$row['latitude'] : null,
            'longitude' => !empty($row['longitude']) ? (float)$row['longitude'] : null,
            'notes' => $row['catatan'] ?? null,
            'is_existing' => $isExisting,
        ];
    }

    protected function validateNetworkRow($row, &$errors, &$warnings, &$mapped, $areasCache, $customersCache, $odpsCache, $options)
    {
        $oltName = $row['nama_olt'] ?? $row['olt'] ?? '';
        $odcName = $row['nama_odc'] ?? $row['odc'] ?? '';
        $odpName = $row['nama_odp'] ?? $row['odp'] ?? '';
        $portNum = (int) ($row['nomor_port'] ?? 0);
        $totalPorts = (int) ($row['total_port_odp'] ?? 8);
        $areaName = $row['nama_area'] ?? '';

        if (empty($oltName)) $errors[] = 'Nama OLT wajib diisi.';
        if (empty($odcName)) $errors[] = 'Nama ODC wajib diisi.';
        if (empty($odpName)) $errors[] = 'Nama ODP wajib diisi.';
        if ($portNum < 1) $errors[] = 'Nomor port harus >= 1.';
        if ($totalPorts < 1) $totalPorts = 8;
        if ($portNum > $totalPorts) $errors[] = "Nomor port ({$portNum}) melebihi total port ODP ({$totalPorts}).";

        $customerCode = $row['kode_pelanggan'] ?? '';
        $customerId = null;
        if (!empty($customerCode)) {
            $customerId = $customersCache[$customerCode] ?? null;
            if (!$customerId) {
                $warnings[] = "Kode pelanggan '{$customerCode}' tidak ditemukan di sistem. Port diimpor tanpa link pelanggan.";
            }
        }

        $snOnt = $row['sn_ont'] ?? '';
        if ($customerId && empty($snOnt)) {
            $warnings[] = "Port terhubung ke pelanggan {$customerCode} tetapi SN ONT kosong.";
        }

        $portStatus = strtolower($row['status_port'] ?? ($customerId ? 'used' : 'available'));
        if (!in_array($portStatus, ['available', 'used', 'reserved', 'fault', 'stop'])) {
            $portStatus = $customerId ? 'used' : 'available';
        }

        $mapped = [
            'olt_name' => $oltName,
            'olt_ip' => $row['ip_olt'] ?? null,
            'pon_port' => (int) ($row['pon_port_olt'] ?? 1),
            'odc_name' => $odcName,
            'splitter_ratio' => $row['splitter_odc'] ?? '1:4',
            'odp_name' => $odpName,
            'total_ports' => $totalPorts,
            'port_number' => $portNum,
            'port_status' => $portStatus,
            'customer_code' => $customerCode,
            'customer_id' => $customerId,
            'serial_number' => $snOnt ?: null,
            'mac_address' => $row['mac_ont'] ?? null,
            'vlan_id' => $row['vlan_id'] ?? null,
            'area_name' => $areaName ?: 'Default',
            'latitude' => !empty($row['latitude']) ? (float)$row['latitude'] : null,
            'longitude' => !empty($row['longitude']) ? (float)$row['longitude'] : null,
        ];
    }

    protected function validateSurveyRow($row, &$errors, &$warnings, &$mapped, $customersCache, $usersCache, $odpsCache)
    {
        $customerCode = $row['kode_pelanggan'] ?? '';
        if (empty($customerCode)) $errors[] = 'Kode Pelanggan wajib diisi.';
        $customerId = $customersCache[$customerCode] ?? null;
        if (!$customerId) $errors[] = "Pelanggan '{$customerCode}' tidak ditemukan di sistem.";

        $surveyorEmail = $row['email_petugas_survey'] ?? '';
        $surveyorId = $usersCache[strtolower($surveyorEmail)] ?? null;
        if (!$surveyorId) $errors[] = "Petugas survey '{$surveyorEmail}' tidak ditemukan.";

        $feasibility = strtolower($row['hasil_kelayakan'] ?? 'feasible');
        if (!in_array($feasibility, ['feasible', 'not_feasible', 'conditional'])) {
            $feasibility = 'feasible';
        }

        $odpName = $row['nama_odp'] ?? '';
        $odp = $odpsCache[strtolower($odpName)] ?? null;
        if ($feasibility === 'feasible' && empty($odpName)) {
            $warnings[] = "Hasil feasible tetapi nama ODP kosong.";
        }

        $mapped = [
            'customer_id' => $customerId,
            'surveyor_id' => $surveyorId,
            'odp_id' => $odp ? $odp->id : null,
            'port_number' => !empty($row['nomor_port']) ? (int)$row['nomor_port'] : null,
            'distance_meters' => !empty($row['jarak_meter']) ? (float)$row['jarak_meter'] : null,
            'feasibility' => $feasibility,
            'signal_loss' => !empty($row['redaman_sinyal']) ? (float)$row['redaman_sinyal'] : null,
            'survey_date' => $row['tgl_survey'] ?? now()->toDateString(),
            'notes' => $row['catatan_survey'] ?? null,
        ];
    }

    protected function validateInstallationRow($row, &$errors, &$warnings, &$mapped, $customersCache, $odpsCache)
    {
        $customerCode = $row['kode_pelanggan'] ?? '';
        if (empty($customerCode)) $errors[] = 'Kode Pelanggan wajib diisi.';
        $customerId = $customersCache[$customerCode] ?? null;
        if (!$customerId) $errors[] = "Pelanggan '{$customerCode}' tidak ditemukan.";

        $sn = $row['sn_ont'] ?? '';
        if (empty($sn)) $errors[] = 'Serial Number ONT wajib diisi.';

        $odpName = $row['nama_odp'] ?? '';
        $odp = $odpsCache[strtolower($odpName)] ?? null;
        if (!$odp) $errors[] = "ODP '{$odpName}' tidak ditemukan.";

        $portNum = (int) ($row['nomor_port_odp'] ?? 1);
        $rxPower = (float) ($row['redaman_rx'] ?? -20.0);

        $mapped = [
            'customer_id' => $customerId,
            'serial_number' => $sn,
            'mac_address' => $row['mac_address'] ?? null,
            'brand' => $row['merk_ont'] ?? 'ZTE',
            'model' => $row['tipe_model'] ?? 'F609',
            'odp_id' => $odp ? $odp->id : null,
            'port_number' => $portNum,
            'rx_power' => $rxPower,
            'pppoe_user' => $row['username_pppoe'] ?? null,
            'pppoe_password' => $row['password_pppoe'] ?? null,
            'vlan_id' => $row['vlan_id'] ?? null,
            'access_mode' => $row['mode_akses'] ?? 'PPPOE',
            'activation_date' => $row['tgl_aktivasi'] ?? now()->toDateString(),
            'is_audited' => !empty($row['lulus_audit_qc']) ? (bool)$row['lulus_audit_qc'] : true,
            'start_time' => $row['jam_pasang_mulai'] ?? null,
            'end_time' => $row['jam_pasang_selesai'] ?? null,
        ];
    }

    protected function validateInvoiceRow($row, &$errors, &$warnings, &$mapped, $customersCache, $invoicesCache)
    {
        $customerCode = $row['kode_pelanggan'] ?? '';
        if (empty($customerCode)) $errors[] = 'Kode Pelanggan wajib diisi.';
        $customerId = $customersCache[$customerCode] ?? null;
        if (!$customerId) $errors[] = "Pelanggan '{$customerCode}' tidak ditemukan.";

        $month = (int) ($row['bulan_periode'] ?? 0);
        $year = (int) ($row['tahun_periode'] ?? 0);
        if ($month < 1 || $month > 12) $errors[] = 'Bulan periode harus bernilai 1 - 12.';
        if ($year < 2000 || $year > 2099) $errors[] = 'Tahun periode tidak valid.';

        $amount = (float) ($row['total_tagihan'] ?? 0);
        if ($amount <= 0) $warnings[] = 'Nominal tagihan bernilai 0 atau negatif.';

        $invNum = $row['nomor_invoice'] ?? null;
        $isExisting = false;
        if ($invNum && isset($invoicesCache[$invNum])) {
            $isExisting = true;
            $warnings[] = "Invoice {$invNum} sudah ada; data akan disinkronkan.";
        }

        $status = strtolower($row['status_invoice'] ?? 'unpaid');
        if (!in_array($status, ['unpaid', 'paid', 'partial', 'cancelled'])) $status = 'unpaid';

        $mapped = [
            'invoice_number' => $invNum,
            'customer_id' => $customerId,
            'period_month' => $month,
            'period_year' => $year,
            'amount' => $amount,
            'issued_date' => $row['tgl_terbit'] ?? now()->toDateString(),
            'due_date' => $row['tgl_jatuh_tempo'] ?? now()->addDays(7)->toDateString(),
            'status' => $status,
            'billing_type' => in_array(strtolower($row['jenis_tagihan'] ?? ''), ['prabayar', 'pascabayar', 'prorata']) ? strtolower($row['jenis_tagihan']) : 'prabayar',
            'notes' => $row['catatan_invoice'] ?? null,
            'is_existing' => $isExisting,
        ];
    }

    protected function validatePaymentRow($row, &$errors, &$warnings, &$mapped, $invoicesCache, $usersCache)
    {
        $invNum = $row['nomor_invoice'] ?? '';
        if (empty($invNum)) $errors[] = 'Nomor Invoice wajib diisi.';
        $invoiceId = $invoicesCache[$invNum] ?? null;
        if (!$invoiceId) $errors[] = "Nomor Invoice '{$invNum}' tidak terdaftar.";

        $amount = (float) ($row['nominal_bayar'] ?? 0);
        if ($amount <= 0) $errors[] = 'Nominal pembayaran harus > 0.';

        $method = strtolower($row['metode_bayar'] ?? 'transfer');
        if (!in_array($method, ['cash', 'transfer', 'ewallet', 'auto_debit'])) $method = 'transfer';

        $collectorId = null;
        $collectorEmail = $row['email_penerima'] ?? '';
        if (!empty($collectorEmail)) {
            $collectorId = $usersCache[strtolower($collectorEmail)] ?? null;
        }

        $mapped = [
            'invoice_id' => $invoiceId,
            'amount' => $amount,
            'payment_date' => $row['tgl_bayar'] ?? now()->toDateString(),
            'payment_method' => $method,
            'reference_number' => $row['nomor_referensi'] ?? null,
            'collected_by' => $collectorId,
            'notes' => $row['catatan'] ?? null,
        ];
    }

    // ── Item Importers ──────────────────────────────────────────────

    protected function importCustomerItem(array $data, string $dupMode, bool $autoCreateArea): string
    {
        // 1. Resolve area
        $areaId = $data['area_id'];
        if (!$areaId && $autoCreateArea && !empty($data['area'])) {
            $area = Area::firstOrCreate(['name' => $data['area']]);
            $areaId = $area->id;
        }

        $customerCode = $data['customer_code'];
        $existing = null;
        if ($customerCode) {
            $existing = Customer::where('customer_code', $customerCode)->first();
        }

        if ($existing) {
            if ($dupMode === 'skip') {
                return 'skipped';
            }
            // Update
            $existing->update([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? $existing->email,
                'area' => $data['area'],
                'area_id' => $areaId ?: $existing->area_id,
                'address' => $data['address'],
                'package_id' => $data['package_id'] ?: $existing->package_id,
                'base_amount' => $data['base_amount'],
                'installation_fee' => $data['installation_fee'],
                'service_status' => $data['service_status'],
                'billing_type' => $data['billing_type'],
                'status' => $data['status'],
                'sales_id' => $data['sales_id'] ?: $existing->sales_id,
                'latitude' => $data['latitude'] ?: $existing->latitude,
                'longitude' => $data['longitude'] ?: $existing->longitude,
                'notes' => $data['notes'] ?: $existing->notes,
            ]);
            return 'updated';
        }

        // Insert new
        Customer::create([
            'customer_code' => $customerCode,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'area' => $data['area'],
            'area_id' => $areaId,
            'address' => $data['address'],
            'package_id' => $data['package_id'],
            'base_amount' => $data['base_amount'],
            'installation_fee' => $data['installation_fee'],
            'service_status' => $data['service_status'],
            'billing_type' => $data['billing_type'],
            'status' => $data['status'],
            'registration_date' => $data['registration_date'],
            'activation_date' => $data['activation_date'],
            'sales_id' => $data['sales_id'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'notes' => $data['notes'],
        ]);

        return 'inserted';
    }

    protected function importNetworkItem(array $data, string $dupMode, bool $autoCreateArea): string
    {
        // 1. Resolve area
        $area = Area::firstOrCreate(['name' => $data['area_name']]);

        // 2. Resolve or create OLT
        $olt = Olt::firstOrCreate(
            ['name' => $data['olt_name']],
            [
                'area_id' => $area->id,
                'ip_address' => $data['olt_ip'] ?? '192.168.1.1',
                'brand' => 'ZTE',
                'total_pon_ports' => 16,
                'status' => 'active'
            ]
        );

        // 3. Resolve OLT PON
        $oltPon = OltPon::firstOrCreate(
            ['olt_id' => $olt->id, 'port_number' => $data['pon_port']],
            ['name' => "PON {$data['pon_port']}", 'capacity' => 64, 'status' => 'active']
        );

        // 4. Resolve ODC
        $odc = Odc::firstOrCreate(
            ['name' => $data['odc_name']],
            [
                'olt_id' => $olt->id,
                'pon_id' => $oltPon->id,
                'area_id' => $area->id,
                'splitter_ratio' => $data['splitter_ratio'] ?? '1:4',
                'capacity' => 48,
                'status' => 'active',
            ]
        );

        // 5. Resolve ODP
        $odp = Odp::firstOrCreate(
            ['name' => $data['odp_name']],
            [
                'odc_id' => $odc->id,
                'area_id' => $area->id,
                'total_ports' => $data['total_ports'],
                'used_ports' => 0,
                'status' => 'active',
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ]
        );

        // 6. Resolve Port
        $odpPort = OdpPort::updateOrCreate(
            ['odp_id' => $odp->id, 'port_number' => $data['port_number']],
            ['status' => $data['port_status']]
        );

        // 7. Connect Customer & ONT if provided
        if (!empty($data['customer_id']) && !empty($data['serial_number'])) {
            $ont = Ont::where('serial_number', $data['serial_number'])->first();
            if ($ont) {
                $ont->update([
                    'customer_id' => $data['customer_id'],
                    'odp_id' => $odp->id,
                    'odp_port_id' => $odpPort->id,
                    'port_number' => $data['port_number'],
                    'mac_address' => $data['mac_address'] ?: $ont->mac_address,
                    'vlan_id' => $data['vlan_id'] ?: $ont->vlan_id,
                    'status' => 'active',
                ]);
            } else {
                Ont::create([
                    'customer_id' => $data['customer_id'],
                    'serial_number' => $data['serial_number'],
                    'mac_address' => $data['mac_address'],
                    'odp_id' => $odp->id,
                    'odp_port_id' => $odpPort->id,
                    'port_number' => $data['port_number'],
                    'area_id' => $area->id,
                    'vlan_id' => $data['vlan_id'],
                    'brand' => 'ZTE',
                    'model' => 'F609',
                    'status' => 'active',
                ]);
            }

            // Sync used ports count
            $usedCount = Ont::where('odp_id', $odp->id)->count();
            $odp->update([
                'used_ports' => $usedCount,
                'status' => ($usedCount >= $odp->total_ports) ? 'full' : 'active'
            ]);
        }

        return 'inserted';
    }

    protected function importSurveyItem(array $data, string $dupMode): string
    {
        // Avoid duplicate survey on same customer and same date
        $existing = Survey::where('customer_id', $data['customer_id'])
            ->whereDate('survey_date', $data['survey_date'])
            ->first();

        if ($existing && $dupMode === 'skip') {
            return 'skipped';
        }

        Survey::create($data);

        // Update customer status to survey
        Customer::where('id', $data['customer_id'])->update(['status' => 'survey']);

        return 'inserted';
    }

    protected function importInstallationItem(array $data, string $dupMode): string
    {
        $sn = $data['serial_number'];
        $ont = Ont::where('serial_number', $sn)->first();

        if ($ont) {
            if ($dupMode === 'skip') {
                return 'skipped';
            }
            $ont->update([
                'customer_id' => $data['customer_id'],
                'odp_id' => $data['odp_id'],
                'port_number' => $data['port_number'],
                'rx_power' => $data['rx_power'],
                'pppoe_user' => $data['pppoe_user'],
                'pppoe_password' => $data['pppoe_password'],
                'vlan_id' => $data['vlan_id'],
                'access_mode' => $data['access_mode'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => 'active',
            ]);
        } else {
            Ont::create([
                'serial_number' => $sn,
                'customer_id' => $data['customer_id'],
                'odp_id' => $data['odp_id'],
                'port_number' => $data['port_number'],
                'rx_power' => $data['rx_power'],
                'brand' => $data['brand'],
                'model' => $data['model'],
                'mac_address' => $data['mac_address'],
                'pppoe_user' => $data['pppoe_user'],
                'pppoe_password' => $data['pppoe_password'],
                'vlan_id' => $data['vlan_id'],
                'access_mode' => $data['access_mode'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'status' => 'active',
            ]);
        }

        // Mark port as used
        if ($data['odp_id'] && $data['port_number']) {
            OdpPort::updateOrCreate(
                ['odp_id' => $data['odp_id'], 'port_number' => $data['port_number']],
                ['status' => 'used']
            );
            $odp = Odp::find($data['odp_id']);
            if ($odp) {
                $used = Ont::where('odp_id', $odp->id)->count();
                $odp->update([
                    'used_ports' => $used,
                    'status' => ($used >= $odp->total_ports) ? 'full' : 'active'
                ]);
            }
        }

        // Update customer activation & audit flag
        Customer::where('id', $data['customer_id'])->update([
            'status' => 'active',
            'activation_date' => $data['activation_date'],
            'is_audited' => $data['is_audited'],
        ]);

        return 'inserted';
    }

    protected function importInvoiceItem(array $data, string $dupMode): string
    {
        $invNum = $data['invoice_number'];
        $existing = null;
        if ($invNum) {
            $existing = Invoice::where('invoice_number', $invNum)->first();
        }

        if ($existing) {
            if ($dupMode === 'skip') {
                return 'skipped';
            }
            $existing->update([
                'amount' => $data['amount'],
                'status' => $data['status'],
                'due_date' => $data['due_date'],
                'issued_date' => $data['issued_date'],
                'billing_type' => $data['billing_type'],
                'notes' => $data['notes'],
            ]);
            return 'updated';
        }

        Invoice::create([
            'invoice_number' => $invNum,
            'customer_id' => $data['customer_id'],
            'period_month' => $data['period_month'],
            'period_year' => $data['period_year'],
            'amount' => $data['amount'],
            'due_date' => $data['due_date'],
            'issued_date' => $data['issued_date'],
            'status' => $data['status'],
            'billing_type' => $data['billing_type'],
            'notes' => $data['notes'],
        ]);

        return 'inserted';
    }

    protected function importPaymentItem(array $data, string $dupMode): string
    {
        $ref = $data['reference_number'];
        if ($ref) {
            $existing = Payment::where('reference_number', $ref)->first();
            if ($existing && $dupMode === 'skip') {
                return 'skipped';
            }
        }

        $invoice = Invoice::findOrFail($data['invoice_id']);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'],
            'payment_method' => $data['payment_method'],
            'reference_number' => $ref,
            'collected_by' => $data['collected_by'],
            'notes' => $data['notes'],
            'status' => 'verified',
        ]);

        // Auto reconcile invoice status based on total paid
        $totalPaid = $invoice->payments()->sum('amount');
        if ($totalPaid >= $invoice->amount) {
            $invoice->update(['status' => 'paid']);
        } elseif ($totalPaid > 0) {
            $invoice->update(['status' => 'partial']);
        }

        return 'inserted';
    }
}
