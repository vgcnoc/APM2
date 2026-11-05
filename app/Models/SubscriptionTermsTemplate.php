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
        $installFee = 'Rp ' . number_format($customer->installation_fee ?? 0, 0, ',', '.');
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
            '{biaya_pasang}' => $installFee,
            '{tanggal}' => $todayFormatted,
            '{perusahaan}' => $companyName,
            '{petugas}' => $employeeName,
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
            ['token' => '{biaya_pasang}', 'label' => 'Biaya Pasang Baru / PSB', 'example' => 'Rp 120.000'],
            ['token' => '{tanggal}', 'label' => 'Tanggal Penandatanganan', 'example' => '05 November 2026'],
            ['token' => '{perusahaan}', 'label' => 'Nama ISP / Perusahaan', 'example' => 'PT VIRUZS GLOBAL CONNECTION'],
            ['token' => '{petugas}', 'label' => 'Nama Petugas yang Bertugas', 'example' => 'Super Admin'],
        ];
    }
}

