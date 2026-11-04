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

        $companyName = Setting::get('company_name', config('app.name', 'Vizbill ISP'));
        $packageName = $customer->package?->name ?? 'Paket Internet Standard';
        $packageSpeed = $customer->package?->speed ? ($customer->package->speed . ' Mbps') : '-';
        $packagePrice = 'Rp ' . number_format($customer->base_amount ?: ($customer->package?->price ?? 0), 0, ',', '.');
        $installFee = 'Rp ' . number_format($customer->installation_fee ?? 0, 0, ',', '.');
        $employeeName = $employee?->name ?? auth()->user()?->name ?? 'Petugas Lapangan';
        $todayFormatted = Carbon::now()->translatedFormat('d F Y');

        $replacements = [
            '{nama_pelanggan}' => $customer->name ?? '-',
            '{nomor_layanan}' => $customer->customer_code ?? '-',
            '{id_pelanggan}' => $customer->customer_code ?? '-',
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
            ['token' => '{nama_pelanggan}', 'label' => 'Nama Pelanggan', 'example' => 'Budi Santoso'],
            ['token' => '{nomor_layanan}', 'label' => 'ID / Kode Pelanggan', 'example' => 'CUST-00214'],
            ['token' => '{telepon}', 'label' => 'Nomor WhatsApp / HP', 'example' => '08123456789'],
            ['token' => '{email}', 'label' => 'Email Pelanggan', 'example' => 'budi@gmail.com'],
            ['token' => '{alamat}', 'label' => 'Alamat Pemasangan', 'example' => 'Jl. Merdeka No. 10'],
            ['token' => '{area}', 'label' => 'Area / Wilayah', 'example' => 'Desa Sirnajaya'],
            ['token' => '{paket}', 'label' => 'Nama Paket Internet', 'example' => 'Paket Family 20M'],
            ['token' => '{kecepatan}', 'label' => 'Kecepatan Paket', 'example' => '20 Mbps'],
            ['token' => '{tarif}', 'label' => 'Biaya Bulanan (Rp)', 'example' => 'Rp 150.000'],
            ['token' => '{biaya_pasang}', 'label' => 'Biaya Pasang Baru / PSB', 'example' => 'Rp 100.000'],
            ['token' => '{tanggal}', 'label' => 'Tanggal Penandatanganan', 'example' => '04 November 2026'],
            ['token' => '{perusahaan}', 'label' => 'Nama ISP / Perusahaan', 'example' => 'Vizbill Internet'],
            ['token' => '{petugas}', 'label' => 'Nama Petugas yang Bertugas', 'example' => 'Super Admin'],
        ];
    }
}

