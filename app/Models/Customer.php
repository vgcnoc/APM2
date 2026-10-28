<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Ont;
use App\Models\Odp;
use App\Models\OdpPort;
use App\Models\Survey;
use App\Models\Ticket;
use App\Models\TechnicianSchedule;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Voucher;
use App\Models\PgsTicket;
use App\Models\PgkTicket;
use App\Models\CbpRequest;
use App\Models\Reseller;
use App\Models\AffiliateCommission;
use App\Models\TargetMarketLog;
use App\Models\MaterialTransaction;
use App\Models\User;
use App\Models\WaLog;
use App\Models\AuditLog;
use App\Services\RadiusService;

class Customer extends Model
{
    use HasFactory, \App\Traits\HasDataScope;

    protected $fillable = [
        'customer_code',
        'name',
        'email',
        'phone',
        'address',
        'user_id',
        'area',
        'identity_photo',
        'latitude',
        'longitude',
        'package_id',
        'billing_type',
        'billing_cycle',
        'billing_day',
        'is_reseller',
        'status',
        'service_status',
        'registration_date',
        'activation_date',
        'notes',
        'base_amount',
        'installation_fee',
        'sales_id',
        'referral_code',
        'is_migrated',
        'is_audited',
        'area_id',
        'tax_ppn',
        'tax_bhp',
        'tax_uso',
        'cancel_reason',
        'cancel_stage',
        'cancel_date',
        'suspend_start',
        'suspend_end',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'registration_date' => 'date:Y-m-d',
            'activation_date' => 'date:Y-m-d',
            'cancel_date' => 'date:Y-m-d',
            'suspend_start' => 'date:Y-m-d',
            'suspend_end' => 'date:Y-m-d',
            'is_audited' => 'boolean',
            'is_migrated' => 'boolean',
            'connection_disabled' => 'boolean',
            'connection_access_pending' => 'boolean',
            'connection_disable_state' => 'array',
            'billing_isolated_at' => 'datetime',
            'billing_isolation_pending' => 'boolean',
        ];
    }

    protected $appends = [
        'customer_id',
    ];

    protected $hidden = ['connection_disable_state'];

    public static bool $isPurging = false;
    public static bool $isMigrating = false;


    /**
     * Alias customer_id attribute returning customer_code for frontend compatibility
     */
    public function getCustomerIdAttribute(): ?string
    {
        return $this->customer_code;
    }

    /**
     * Resolves billing_type defaulting to the system setting if empty
     */
    public function getBillingTypeAttribute(?string $value): string
    {
        if (empty($value)) {
            return Setting::get('billing_type', 'prabayar');
        }
        return $value;
    }

    // ── Relationships ──────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Area tempat pelanggan berada
     */
    public function areaModel(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /**
     * Alias area untuk relasi ke Area
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /**
     * Alias areaRef untuk kompatibilitas WhatsApp Blast & Notifikasi
     */
    public function areaRef(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /**
     * Paket internet yang diambil pelanggan
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(InternetPackage::class, 'package_id');
    }

    /**
     * Sales / Marketing yang mendaftarkan pelanggan
     */
    public function sales(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    /**
     * ONT yang terpasang di pelanggan (1:1 secara logika)
     */
    public function ont(): HasOne
    {
        return $this->hasOne(Ont::class);
    }

    public function onts(): HasMany
    {
        return $this->hasMany(Ont::class);
    }

    /**
     * Riwayat invoice pelanggan
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Riwayat pembayaran pelanggan
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Tiket gangguan pelanggan
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Riwayat survey lokasi pelanggan
     */
    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    /**
     * Jadwal teknisi untuk pelanggan ini
     */
    public function technicianSchedules(): HasMany
    {
        return $this->hasMany(TechnicianSchedule::class);
    }

    /**
     * Data reseller (jika is_reseller = true)
     */
    public function reseller(): HasOne
    {
        return $this->hasOne(Reseller::class);
    }

    /**
     * Tiket Penanganan Sigap (PGS) untuk pelanggan ini
     */
    public function pgsTickets(): HasMany
    {
        return $this->hasMany(PgsTicket::class);
    }

    /**
     * Riwayat Permintaan CBP / Cabut Perangkat
     */
    public function cbpRequests(): HasMany
    {
        return $this->hasMany(CbpRequest::class);
    }

    /**
     * Permintaan CBP terbaru untuk pelanggan ini
     */
    public function latestCbpRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CbpRequest::class)->latestOfMany();
    }

    /**
     * Catatan komisi pinalti / clawback fee booking jika berhenti < 4 bulan
     */
    public function clawbackCommission(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AffiliateCommission::class, 'customer_id')->where('type', 'clawback_booking');
    }

    /**
     * Catatan komisi pinalti / clawback fee survey jika berhenti < 4 bulan
     */
    public function clawbackSurveyCommission(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AffiliateCommission::class, 'customer_id')->where('type', 'clawback_survey');
    }

    public function pgkTickets(): HasMany
    {
        return $this->hasMany(PgkTicket::class);
    }

    public function installedMaterials(): HasMany
    {
        return $this->hasMany(CustomerInstalledMaterial::class);
    }

    public function activeInstalledMaterials(): HasMany
    {
        return $this->hasMany(CustomerInstalledMaterial::class)->whereIn('status', ['installed', 'partially_replaced']);
    }

    public function materialDismantles(): HasMany
    {
        return $this->hasMany(CustomerMaterialDismantle::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function affiliateCommissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class, 'customer_id');
    }

    public function targetMarketLogs(): HasMany
    {
        return $this->hasMany(TargetMarketLog::class);
    }

    public function materialTransactions(): HasMany
    {
        return $this->hasMany(MaterialTransaction::class);
    }

    // ── Computed ───────────────────────────────────────────────

    /**
     * Hitung durasi masa aktif pelanggan
     */
    public function getActiveDurationAttribute(): array
    {
        $startDate = $this->activation_date 
            ? \Carbon\Carbon::parse($this->activation_date) 
            : ($this->registration_date ? \Carbon\Carbon::parse($this->registration_date) : ($this->created_at ? \Carbon\Carbon::parse($this->created_at) : now()));
        
        $endDate = $this->cancel_date ? \Carbon\Carbon::parse($this->cancel_date) : now();

        $diffInDays = max(1, (int) $startDate->diffInDays($endDate));
        $diffInMonths = (int) $startDate->diffInMonths($endDate);
        $remainingDays = $diffInDays % 30;

        if ($diffInMonths >= 12) {
            $years = floor($diffInMonths / 12);
            $months = $diffInMonths % 12;
            $text = "{$years} tahun " . ($months > 0 ? "{$months} bulan " : '') . ($remainingDays > 0 ? "{$remainingDays} hari" : '');
        } elseif ($diffInMonths > 0) {
            $text = "{$diffInMonths} bulan" . ($remainingDays > 0 ? " {$remainingDays} hari" : '');
        } else {
            $text = "{$diffInDays} hari";
        }

        return [
            'months' => $diffInMonths,
            'days' => $diffInDays,
            'text' => trim($text),
            'is_under_4_months' => $diffInMonths < 4,
            'start_date' => $startDate->format('d M Y'),
            'end_date' => $endDate->format('d M Y'),
        ];
    }

    /**
     * Informasi ONT & ODP yang terhubung
     */
    public function getNetworkInfoAttribute(): ?array
    {
        $ont = $this->ont;
        if (!$ont) return null;

        return [
            'ont_sn' => $ont->serial_number,
            'ont_status' => $ont->status,
            'rx_power' => $ont->rx_power,
            'odp' => $ont->odp?->name,
            'odc' => $ont->odp?->odc?->name,
            'olt' => $ont->odp?->odc?->olt?->name,
        ];
    }

    /**
     * Status label yang readable
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'booking' => 'Booking',
            'survey' => 'Proses Survey',
            'installing' => 'Proses Pemasangan',
            'active' => 'Aktif',
            'suspended' => 'ISOLIR',
            'terminated' => 'Berhenti',
            default => $this->status,
        };
    }

    /**
     * Warna badge status
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'booking' => 'yellow',
            'survey' => 'blue',
            'installing' => 'indigo',
            'active' => 'green',
            'suspended' => 'orange',
            'terminated' => 'red',
            default => 'gray',
        };
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBooking($query)
    {
        return $query->whereIn('status', ['booking', 'survey', 'installing', 'active']);
    }

    public function scopeSurvey($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('status', ['survey', 'installing', 'active'])
              ->orWhere(function ($sub) {
                  $sub->where('status', 'terminated')
                      ->where('cancel_stage', 'survey')
                      ->whereHas('surveys', fn($sq) => $sq->where('feasibility', 'not_feasible'));
              });
        });
    }

    public function scopeInstalled($query)
    {
        return $query->where('status', 'installing');
    }

    public function scopeSearch($query, ?string $search)
    {
        if (!$search) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('customer_code', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('address', 'like', "%{$search}%");
        });
    }

    // ── Auto-generate customer code ────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (static::$isMigrating) {
                $customer->is_migrated = true;
            }
            if (empty($customer->customer_code)) {
                $lastCode = static::orderByDesc('id')->value('customer_code');
                $nextNumber = $lastCode
                    ? (int) substr($lastCode, 4) + 1
                    : 1;
                $customer->customer_code = 'CUS-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            }

            if (empty($customer->registration_date)) {
                $customer->registration_date = now()->toDateString();
            }
        });

        static::created(function (Customer $customer) {
            if ($customer->referral_code && !$customer->sales_id) {
                $aff = \App\Models\AffiliateProfile::where('referral_code', $customer->referral_code)->first();
                if ($aff) {
                    $customer->sales_id = $aff->user_id;
                    $customer->saveQuietly();
                }
            }

            if (!static::$isMigrating && !$customer->is_migrated && $customer->sales_id && !in_array($customer->service_status, ['gratis', 'free_home'])) {
                try {
                    \App\Services\AffiliateService::recordBookingCommission($customer);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[Affiliate] Booking commission error: ' . $e->getMessage());
                }
            }
        });

        static::updated(function (Customer $customer) {
            // 1. Sinkronisasi Affiliate Booking Commission jika sales_id berubah
            if (!static::$isMigrating && !$customer->is_migrated && $customer->wasChanged('sales_id') && $customer->sales_id && !in_array($customer->service_status, ['gratis', 'free_home'])) {
                try {
                    \App\Services\AffiliateService::recordBookingCommission($customer);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[Affiliate] Booking commission error: ' . $e->getMessage());
                }
            }

            // 2. Sinkronisasi Area ke ONT, Jadwal Teknisi, Survey, PGS, & PGK
            if ($customer->wasChanged('area_id')) {
                try {
                    if ($customer->area_id) {
                        $areaName = \App\Models\Area::where('id', $customer->area_id)->value('name');
                        if ($areaName && $customer->area !== $areaName) {
                            $customer->newQuery()->where('id', $customer->id)->update(['area' => $areaName]);
                        }
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('onts', 'area_id')) {
                        \App\Models\Ont::where('customer_id', $customer->id)->update(['area_id' => $customer->area_id]);
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('technician_schedules', 'area_id')) {
                        \App\Models\TechnicianSchedule::where('customer_id', $customer->id)->update(['area_id' => $customer->area_id]);
                    }
                    if (\Illuminate\Support\Facades\Schema::hasColumn('surveys', 'area_id')) {
                        \App\Models\Survey::where('customer_id', $customer->id)->update(['area_id' => $customer->area_id]);
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('pgs_tickets') && \Illuminate\Support\Facades\Schema::hasColumn('pgs_tickets', 'area_id')) {
                        \App\Models\PgsTicket::where('customer_id', $customer->id)->update(['area_id' => $customer->area_id]);
                    }
                    if (\Illuminate\Support\Facades\Schema::hasTable('pgk_tickets') && \Illuminate\Support\Facades\Schema::hasColumn('pgk_tickets', 'area_id')) {
                        \App\Models\PgkTicket::where('customer_id', $customer->id)->update(['area_id' => $customer->area_id]);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[Customer::updated] Area sync error: ' . $e->getMessage());
                }
            }

            // 3. Sinkronisasi Profil Akun Login Portal Pelanggan jika nama/email/telepon diedit (Requirement 4)
            if ($customer->wasChanged(['name', 'email', 'phone']) && $customer->user_id) {
                try {
                    $user = \App\Models\User::find($customer->user_id);
                    if ($user) {
                        $userUpdates = [];
                        if ($customer->wasChanged('name')) $userUpdates['name'] = $customer->name;
                        if ($customer->wasChanged('email') && $customer->email) $userUpdates['email'] = $customer->email;
                        if ($customer->wasChanged('phone') && $customer->phone) $userUpdates['phone'] = $customer->phone;
                        if (!empty($userUpdates)) {
                            $user->update($userUpdates);
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[Customer::updated] User portal sync error: ' . $e->getMessage());
                }
            }

            // 4. Otomatis buat tiket Cabut Perangkat (CBP) jika status menjadi terminated / berhenti
            if (($customer->wasChanged('status') && $customer->status === 'terminated') ||
                ($customer->wasChanged('service_status') && $customer->service_status === 'berhenti')) {
                $hasCbp = \App\Models\CbpRequest::where('customer_id', $customer->id)
                    ->whereIn('status', ['pending', 'assigned'])
                    ->exists();
                if (!$hasCbp) {
                    try {
                        \App\Models\CbpRequest::create([
                            'customer_id' => $customer->id,
                            'status' => 'pending',
                            'notes' => 'Otomatis dibuat saat status pelanggan diubah menjadi Terminated / Berhenti.',
                            'created_by' => auth()->id() ?? 1,
                        ]);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('[CbpRequest] Auto-create CBP error: ' . $e->getMessage());
                    }
                }
            }

            // 5. Sinkronisasi RADIUS jika paket atau status layanan berubah
            if ($customer->wasChanged(['package_id', 'service_status', 'status'])) {
                try {
                    if (class_exists(\App\Services\RadiusService::class)) {
                        $radius = app(\App\Services\RadiusService::class);
                        if ($radius && $radius->enabled()) {
                            $radius->syncCustomer($customer);
                            foreach (array_keys($radius->customerAccounts($customer)) as $username) {
                                $radius->disconnect($username);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[Customer::updated] Radius sync error: ' . $e->getMessage());
                }
            }
        });

        static::deleting(function (Customer $customer) {
            static::$isPurging = true;
            try {
                $customer->purgeConnectedData();
            } finally {
                static::$isPurging = false;
            }
        });
    }

    /**
     * Hapus seluruh data dan perangkat yang terhubung dengan pelanggan secara cerdas dan bersih:
     * 1. Kembalikan semua perangkat ONT ke stok gudang (status: 'Belum Set/Baru Input'), bersihkan kredensial & foto instalasi
     * 2. Bebaskan port ODP yang sedang digunakan oleh ONT & perbarui status ODP
     * 3. Bebaskan port ODP yang sempat di-reservasi oleh riwayat survey & hapus foto survey dari storage
     * 4. Hapus user & putuskan koneksi aktif di FreeRADIUS (PPPoE, Hotspot Gratis, Voucher)
     * 5. Hapus voucher hotspot gratis milik pelanggan
     * 6. Hapus riwayat pembayaran beserta foto bukti transfer dari storage
     * 7. Hapus seluruh invoice pelanggan
     * 8. Hapus tiket gangguan & jadwal teknisi
     * 9. Hapus tiket penanganan sigap (PGS) beserta material, permintaan material, pivot teknisi & foto dokumentasi
     * 10. Hapus tiket keluhan pelanggan (PGK) beserta material, permintaan material, pivot teknisi & foto dokumentasi
     * 11. Hapus permintaan cabut perangkat (CBP) beserta pivot teknisi & foto
     * 12. Kembalikan seluruh material terpasang dan transaksi material pelanggan ke stok gudang (Area & Global)
     * 13. Hapus data reseller & akun pengguna reseller (jika tipe reseller)
     * 14. Hapus komisi afiliasi & log target market & komisi insentif
     * 15. Hapus akun login portal pelanggan (User dengan role customer)
     * 16. Hapus foto identitas / KTP dari storage
     * 17. Hapus riwayat log pesan WhatsApp yang ditujukan ke pelanggan ini
     */
    public function purgeConnectedData(): void
    {
        $radius = app(\App\Services\RadiusService::class);

        // 1 & 2. Tangani semua ONT yang terhubung: kembalikan ke data ONT status 'Belum Set/Baru Input' (Gudang) (Requirement 3)
        try {
            $onts = Ont::where('customer_id', $this->id)->get();
            foreach ($onts as $ont) {
                // Bebaskan port ODP
                try {
                    if ($ont->odp_port_id) {
                        OdpPort::where('id', $ont->odp_port_id)->update([
                            'status' => 'available',
                            'description' => null,
                        ]);
                    } elseif ($ont->odp_id && $ont->port_number) {
                        OdpPort::where('odp_id', $ont->odp_id)
                            ->where('port_number', $ont->port_number)
                            ->update([
                                'status' => 'available',
                                'description' => null,
                            ]);
                    }

                    // Sinkronisasi counter used_ports pada ODP
                    if ($ont->odp_id && ($odp = Odp::find($ont->odp_id))) {
                        $usedPorts = OdpPort::where('odp_id', $odp->id)->where('status', 'used')->count();
                        $odpStatus = ($usedPorts >= $odp->total_ports) ? 'full' : 'active';
                        $odp->update([
                            'used_ports' => $usedPorts,
                            'status' => $odpStatus,
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('[Customer::purgeConnectedData] Error releasing ODP port: ' . $e->getMessage());
                }

                // Hapus akun RADIUS PPPoE dan Hotspot dari FreeRADIUS
                if ($radius && $radius->enabled()) {
                    $radius->guard(function ($rad) use ($ont) {
                        if ($ont->pppoe_user) {
                            $rad->removeUser($ont->pppoe_user);
                            $rad->disconnect($ont->pppoe_user);
                        }
                        if ($ont->hotspot_user) {
                            $rad->removeUser($ont->hotspot_user);
                            $rad->disconnect($ont->hotspot_user);
                        }
                    });
                }

                // Hapus file foto instalasi ONT dari storage
                $ontPhotos = ['photo_odp', 'photo_installation', 'photo_ont', 'photo_customer', 'photo_redaman'];
                foreach ($ontPhotos as $photoField) {
                    try {
                        if ($ont->{$photoField} && Storage::disk('public')->exists($ont->{$photoField})) {
                            Storage::disk('public')->delete($ont->{$photoField});
                        }
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }

                // Kembalikan ONT ke status 'Belum Set/Baru Input' dan unassign dari pelanggan & ODP (Requirement 3)
                $ont->update([
                    'customer_id' => null,
                    'status' => 'Belum Set/Baru Input', // Status Belum Set (Gudang)
                    'odp_id' => null,
                    'odp_port_id' => null,
                    'port_number' => null,
                    'rx_power' => null,
                    'tx_power' => null,
                    'pppoe_user' => null,
                    'pppoe_password' => null,
                    'hotspot_user' => null,
                    'hotspot_password' => null,
                    'vlan_id' => null,
                    'hotspot_vlan_id' => null,
                    'access_mode' => 'PPPOE',
                    'ip_login' => null,
                    'login_user' => null,
                    'login_password' => null,
                    'free_hotspot' => false,
                    'photo_odp' => null,
                    'photo_installation' => null,
                    'photo_ont' => null,
                    'photo_customer' => null,
                    'photo_redaman' => null,
                    'description' => 'Kembali ke data ONT dengan status Belum Set (Gudang) karena pelanggan ' . $this->name . ' (' . $this->customer_code . ') dihapus pada ' . now()->format('d/m/Y H:i'),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error handling ONTs: ' . $e->getMessage());
        }

        // 3. Tangani riwayat Survey & Port ODP yang di-reservasi
        try {
            $surveys = Survey::where('customer_id', $this->id)->get();
            foreach ($surveys as $survey) {
                if ($survey->odp_id && $survey->port_number) {
                    OdpPort::where('odp_id', $survey->odp_id)
                        ->where('port_number', $survey->port_number)
                        ->where('status', 'reserved')
                        ->update([
                            'status' => 'available',
                            'description' => null,
                        ]);
                }

                if (is_array($survey->photos)) {
                    foreach ($survey->photos as $photo) {
                        try {
                            if ($photo && Storage::disk('public')->exists($photo)) {
                                Storage::disk('public')->delete($photo);
                            }
                        } catch (\Throwable $e) {
                            // ignore
                        }
                    }
                }
                $survey->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error handling surveys: ' . $e->getMessage());
        }

        // 4. Tangani Voucher Hotspot
        try {
            $vouchers = Voucher::where('customer_id', $this->id)->get();
            foreach ($vouchers as $voucher) {
                if ($radius && $radius->enabled() && $voucher->username) {
                    $radius->guard(function ($rad) use ($voucher) {
                        $rad->removeUser($voucher->username);
                        $rad->disconnect($voucher->username);
                    });
                }
                $voucher->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error handling vouchers: ' . $e->getMessage());
        }

        // 5. Tangani Pembayaran & Bukti Transfer, Insentif, lalu Invoices (Termasuk Kasbon Reseller)
        try {
            $invoices = Invoice::where('customer_id', $this->id)->get();
            foreach ($invoices as $inv) {
                foreach ($inv->payments as $payment) {
                    \App\Models\Incentive::where('reference_id', 'payment_' . $payment->id)->delete();
                    try {
                        if ($payment->proof_image && Storage::disk('public')->exists($payment->proof_image)) {
                            Storage::disk('public')->delete($payment->proof_image);
                        }
                    } catch (\Throwable $e) {}
                    $payment->delete();
                }
            }

            $payments = Payment::where('customer_id', $this->id)->get();
            foreach ($payments as $payment) {
                \App\Models\Incentive::where('reference_id', 'payment_' . $payment->id)->delete();
                try {
                    if ($payment->proof_image && Storage::disk('public')->exists($payment->proof_image)) {
                        Storage::disk('public')->delete($payment->proof_image);
                    }
                } catch (\Throwable $e) {}
                $payment->delete();
            }

            $invoiceIds = $invoices->pluck('id');
            if ($invoiceIds->isNotEmpty()) {
                AffiliateCommission::whereIn('invoice_id', $invoiceIds)->delete();
            }
            Invoice::where('customer_id', $this->id)->delete();
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error handling invoices/payments: ' . $e->getMessage());
        }

        // 6. Tangani Tiket Gangguan & Jadwal Teknisi
        try {
            TechnicianSchedule::where('customer_id', $this->id)->delete();
            Ticket::where('customer_id', $this->id)->delete();
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error handling tickets: ' . $e->getMessage());
        }

        // 7. Tangani Tiket Penanganan Sigap (PGS)
        try {
            $pgsTickets = PgsTicket::where('customer_id', $this->id)->get();
            foreach ($pgsTickets as $pgs) {
                try {
                    if ($pgs->photo_before && Storage::disk('public')->exists($pgs->photo_before)) {
                        Storage::disk('public')->delete($pgs->photo_before);
                    }
                    if ($pgs->photo_after && Storage::disk('public')->exists($pgs->photo_after)) {
                        Storage::disk('public')->delete($pgs->photo_after);
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
                $pgs->technicians()->detach();
                $pgs->materials()->delete();
                $pgs->materialRequests()->delete();
                $pgs->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error handling PGS tickets: ' . $e->getMessage());
        }

        // 8. Tangani Tiket Keluhan (PGK)
        try {
            $pgkTickets = PgkTicket::where('customer_id', $this->id)->get();
            foreach ($pgkTickets as $pgk) {
                try {
                    if ($pgk->photo_before && Storage::disk('public')->exists($pgk->photo_before)) {
                        Storage::disk('public')->delete($pgk->photo_before);
                    }
                    if ($pgk->photo_after && Storage::disk('public')->exists($pgk->photo_after)) {
                        Storage::disk('public')->delete($pgk->photo_after);
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
                $pgk->technicians()->detach();
                $pgk->materials()->delete();
                $pgk->materialRequests()->delete();
                $pgk->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error handling PGK tickets: ' . $e->getMessage());
        }

        // 9. Tangani Permintaan CBP (Cabut Perangkat)
        try {
            $cbpRequests = CbpRequest::where('customer_id', $this->id)->get();
            foreach ($cbpRequests as $cbp) {
                try {
                    if ($cbp->photo && Storage::disk('public')->exists($cbp->photo)) {
                        Storage::disk('public')->delete($cbp->photo);
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
                $cbp->technicians()->detach();
                $cbp->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error handling CBP requests: ' . $e->getMessage());
        }

        // 10. Tangani Seluruh Material Terpasang & Kembalikan ke Stok Gudang (Requirement 3)
        try {
            $installedMaterials = CustomerInstalledMaterial::where('customer_id', $this->id)->get();
            foreach ($installedMaterials as $installed) {
                if ($installed->material_id && (float) $installed->quantity > 0) {
                    \App\Services\CustomerMaterialService::returnMaterialToWarehouse(
                        (int) $installed->material_id,
                        (float) $installed->quantity,
                        $this->area_id,
                        $installed->unit
                    );
                }
                $installed->dismantles()->delete();
                $installed->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error returning installed materials: ' . $e->getMessage());
        }

        // 11. Tangani Transaksi Material Pengeluaran (type: out) ke pelanggan ini & kembalikan stok ke gudang
        try {
            \App\Services\MaterialHandoverService::restoreDispatchedMaterials(
                'instalasi',
                $this->id,
                $this->area_id,
                $this->customer_code ?? ('CUST-' . $this->id)
            );
            DB::table('material_requests')->where('customer_id', $this->id)->delete();
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error handling material transactions: ' . $e->getMessage());
        }

        // 12. Tangani Data Reseller & Seluruh Keuangan Reseller jika ada
        try {
            $resellers = Reseller::where('customer_id', $this->id)->get();
            foreach ($resellers as $reseller) {
                $reseller->delete(); // Otomatis memicu Reseller::deleting hook
            }

            // Cleanup ekstra cerdas: pastikan tidak ada mutasi saldo, request saldo, kasbon, atau voucher nyangkut
            ResellerTransaction::whereHas('reseller', fn($r) => $r->where('customer_id', $this->id))->delete();
            ResellerBalanceRequest::whereHas('reseller', fn($r) => $r->where('customer_id', $this->id))->delete();
            Voucher::where('customer_id', $this->id)->orWhereHas('reseller', fn($r) => $r->where('customer_id', $this->id))->delete();
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error handling reseller: ' . $e->getMessage());
        }

        // 13. Tangani Komisi Afiliasi, Insentif & Target Market
        try {
            AffiliateCommission::where('customer_id', $this->id)->delete();
            TargetMarketLog::where('customer_id', $this->id)->delete();
            if (\Illuminate\Support\Facades\Schema::hasTable('incentives')) {
                DB::table('incentives')
                    ->where('reference_id', 'like', "customer_{$this->id}%")
                    ->orWhere('reference_id', 'like', "%cust_{$this->id}%")
                    ->orWhere('reference_id', 'like', "%_{$this->customer_code}%")
                    ->orWhere('description', 'like', "%({$this->customer_code})%")
                    ->orWhere('description', 'like', "%: {$this->name}%")
                    ->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('deductions')) {
                DB::table('deductions')
                    ->where('reference_id', 'like', "%cust_{$this->id}%")
                    ->orWhere('reference_id', 'like', "%_{$this->customer_code}%")
                    ->orWhere('description', 'like', "%({$this->customer_code})%")
                    ->orWhere('description', 'like', "%: {$this->name}%")
                    ->orWhere('description', 'like', "% {$this->name} (%")
                    ->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error handling affiliate/target market: ' . $e->getMessage());
        }

        // 14. Tangani Akun Login Portal Pelanggan (User)
        try {
            if ($this->user_id && ($user = User::find($this->user_id))) {
                if ($user->hasRole('customer') || $user->role === 'customer') {
                    $user->delete();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error deleting customer user: ' . $e->getMessage());
        }

        // 15. Hapus Foto Identitas / KTP Pelanggan dari Storage
        try {
            if ($this->identity_photo && Storage::disk('public')->exists($this->identity_photo)) {
                Storage::disk('public')->delete($this->identity_photo);
            }
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error deleting identity photo: ' . $e->getMessage());
        }

        // 16. Hapus Log WhatsApp Terkait Pelanggan
        try {
            WaLog::where('recipient_type', 'customer')->where('recipient_id', $this->id)->delete();
        } catch (\Throwable $e) {
            Log::warning('[Customer::purgeConnectedData] Error deleting WaLog: ' . $e->getMessage());
        }
    }

}
