<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SubscriptionTermsTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'content',
        'requires_employee_signature',
        'requires_customer_signature',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'requires_employee_signature' => 'boolean',
        'requires_customer_signature' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scope template aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Relasi ke pembuat template
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke pengubah template
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relasi ke perjanjian yang menggunakan template ini
     */
    public function agreements()
    {
        return $this->hasMany(CustomerAgreement::class, 'terms_template_id');
    }

    /**
     * Render template dengan data customer aktual
     */
    public function renderContent(Customer $customer, ?User $employee = null): string
    {
        $customer->loadMissing(['package', 'areaModel']);

        $companyName = Setting::get('company_name', config('app.name', 'PT VIRUZS GLOBAL CONNECTION'));
        $packageName = $customer->package?->name ?? 'Paket Internet Standard';
        $packageSpeed = $customer->package?->speed_mbps ? ($customer->package->speed_mbps . ' Mbps') : ($customer->package?->speed ?? '-');
        $packagePrice = 'Rp ' . number_format($customer->base_amount ?: ($customer->package?->price ?? 0), 0, ',', '.');
        $rawInstallFee = $customer->installation_fee;
        if (($rawInstallFee === null || (float)$rawInstallFee == 0) && $customer->package?->installation_fee) {
            $rawInstallFee = $customer->package->installation_fee;
        }
        $installFeeVal = (float)($rawInstallFee ?? 0);
        $installFeeFormatted = 'Rp ' . number_format($installFeeVal, 0, ',', '.') . ',-';

        $clientAreaUrl = Setting::get('client_area_url');
        if (empty($clientAreaUrl)) {
            $host = request()->getHost();
            if ($host && $host !== 'localhost' && $host !== '127.0.0.1') {
                $clientAreaUrl = request()->getSchemeAndHttpHost();
            } else {
                $clientAreaUrl = config('app.url', 'https://bill.viruzs.my.id');
            }
        }
        $clientAreaUrl = rtrim($clientAreaUrl, '/');

        $employeeName = $employee?->name ?? auth()->user()?->name ?? 'Petugas Lapangan';
        $todayFormatted = Carbon::now()->translatedFormat('d F Y');
        $nikKtp = $customer->nik ?? $customer->identity_number ?? '-';

        $replacements = [
            '{nama_pelanggan}' => $customer->name ?? '-',
            '{nomor_layanan}' => $customer->customer_code ?? '-',
            '{id_pelanggan}' => $customer->customer_code ?? '-',
            '{nik_ktp}' => $nikKtp,
            '{nik}' => $nikKtp,
            '{telepon}' => $customer->phone ?? '-',
            '{email}' => $customer->email ?? '-',
            '{alamat}' => $customer->address ?? '-',
            '{area}' => $customer->areaModel?->name ?? $customer->area ?? '-',
            '{paket}' => $packageName,
            '{kecepatan}' => $packageSpeed,
            '{tarif}' => $packagePrice,
            '{biaya_pasang}' => $installFeeFormatted,
            '{biaya_registrasi}' => $installFeeFormatted,
            '{client_area_url}' => $clientAreaUrl,
            '{link_aplikasi}' => $clientAreaUrl,
            '{url_aplikasi}' => $clientAreaUrl,
            '{app_url}' => $clientAreaUrl,
            '{tanggal}' => $todayFormatted,
            '{perusahaan}' => $companyName,
            '{petugas}' => $employeeName,
            // Fallback otomatis jika template tersimpan teks statis:
            'Rp 120,000,-' => $installFeeFormatted,
            'Rp 120.000,-' => $installFeeFormatted,
            'https://viruzs.rlradius.com' => $clientAreaUrl,
            'http://viruzs.rlradius.com' => $clientAreaUrl,
        ];

        return strtr($this->content, $replacements);
    }

    /**
     * Daftar token variabel yang didukung beserta label penjelasannya
     */
    public static function getSupportedTokens(): array
    {
        return [
            ['token' => '{nama_pelanggan}', 'label' => 'Nama Pelanggan', 'example' => 'Siti Fatimah'],
            ['token' => '{alamat}', 'label' => 'Alamat Pemasangan', 'example' => 'Kp. Sukamulya RT 01/02'],
            ['token' => '{nik_ktp}', 'label' => 'NIK KTP Pelanggan', 'example' => '3202xxxxxxxxxxxx'],
            ['token' => '{nomor_layanan}', 'label' => 'ID / Kode Pelanggan', 'example' => 'CUS-002568'],
            ['token' => '{id_pelanggan}', 'label' => 'ID Pelanggan (Alternatif)', 'example' => 'CUS-002568'],
            ['token' => '{telepon}', 'label' => 'Nomor WhatsApp / HP', 'example' => '085718293547'],
            ['token' => '{email}', 'label' => 'Email Pelanggan', 'example' => 'pelanggan@gmail.com'],
            ['token' => '{area}', 'label' => 'Area / Wilayah', 'example' => 'Wilayah BC 1'],
            ['token' => '{paket}', 'label' => 'Nama Paket Internet', 'example' => 'INTERNET HEBAT 20 MBPS'],
            ['token' => '{kecepatan}', 'label' => 'Kecepatan Paket', 'example' => '20 Mbps'],
            ['token' => '{tarif}', 'label' => 'Biaya Bulanan (Rp)', 'example' => 'Rp 130.000'],
            ['token' => '{biaya_pasang}', 'label' => 'Biaya Pasang Baru / Registrasi', 'example' => 'Rp 120.000,-'],
            ['token' => '{biaya_registrasi}', 'label' => 'Biaya Registrasi / Pasang Baru', 'example' => 'Rp 120.000,-'],
            ['token' => '{client_area_url}', 'label' => 'Link / URL Aplikasi Client Area', 'example' => 'https://bill.viruzs.my.id'],
            ['token' => '{link_aplikasi}', 'label' => 'Link Aplikasi Client Area (Alternatif)', 'example' => 'https://bill.viruzs.my.id'],
            ['token' => '{tanggal}', 'label' => 'Tanggal Penandatanganan', 'example' => '05 November 2026'],
            ['token' => '{perusahaan}', 'label' => 'Nama ISP / Perusahaan', 'example' => 'PT VIRUZS GLOBAL CONNECTION'],
            ['token' => '{petugas}', 'label' => 'Nama Petugas yang Bertugas', 'example' => 'Super Admin'],
        ];
    }
}

