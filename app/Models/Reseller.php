<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reseller extends Model
{
    /** @use HasFactory<\Database\Factories\ResellerFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'balance',
        'is_active',
    ];

    protected static function booted()
    {
        static::deleting(function (Reseller $reseller) {
            $reseller->purgeConnectedData();
        });
    }

    /**
     * Hapus cerdas seluruh data yang terhubung dengan Reseller:
     * 1. Hapus semua riwayat transaksi mutasi saldo reseller (ResellerTransaction)
     * 2. Hapus semua riwayat permohonan saldo reseller (ResellerBalanceRequest)
     * 3. Hapus semua tagihan kasbon deposit reseller (Invoice is_reseller_balance), beserta seluruh pembayaran dan insentif terkait
     * 4. Hapus seluruh voucher yang dibuat / dimiliki oleh reseller (Voucher)
     * 5. Sinkronkan status is_reseller pada Customer terkait menjadi false
     * 6. Hapus / cabut role reseller pada User terkait
     */
    public function purgeConnectedData(): void
    {
        // 1. Transaksi Mutasi Saldo
        try {
            $this->transactions()->delete();
            ResellerTransaction::where('reseller_id', $this->id)->delete();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Reseller::purgeConnectedData] Error deleting transactions: ' . $e->getMessage());
        }

        // 2. Permohonan / Top Up Saldo
        try {
            $this->balanceRequests()->delete();
            ResellerBalanceRequest::where('reseller_id', $this->id)->delete();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Reseller::purgeConnectedData] Error deleting balance requests: ' . $e->getMessage());
        }

        // 3. Tagihan Kasbon Reseller & Pembayaran Terkait
        try {
            $kasbonInvoices = Invoice::where('is_reseller_balance', true)
                ->where(function($q) {
                    if ($this->customer_id) {
                        $q->where('customer_id', $this->customer_id);
                    }
                    $q->orWhere('notes', 'like', "%Ref: REQ-{$this->id}%")
                      ->orWhere('notes', 'like', "%Reseller #{$this->id}%")
                      ->orWhere('notes', 'like', "%reseller_{$this->id}%");
                })->get();

            foreach ($kasbonInvoices as $inv) {
                foreach ($inv->payments as $payment) {
                    \App\Models\Incentive::where('reference_id', 'payment_' . $payment->id)->delete();
                    try {
                        if ($payment->proof_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($payment->proof_image)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($payment->proof_image);
                        }
                    } catch (\Throwable $e) {}
                    $payment->delete();
                }
                $inv->delete();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Reseller::purgeConnectedData] Error deleting kasbon invoices: ' . $e->getMessage());
        }

        // 4. Voucher Milik Reseller
        try {
            Voucher::where('reseller_id', $this->id)->delete();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Reseller::purgeConnectedData] Error deleting vouchers: ' . $e->getMessage());
        }

        // 5. Update Pelanggan Terkait (is_reseller = false)
        try {
            if ($this->customer_id && ($customer = Customer::find($this->customer_id))) {
                if ($customer->is_reseller) {
                    $customer->update(['is_reseller' => false]);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Reseller::purgeConnectedData] Error updating customer: ' . $e->getMessage());
        }

        // 6. Akun Login User Reseller
        try {
            if ($this->user_id && ($user = User::find($this->user_id))) {
                if (method_exists($user, 'hasRole') && $user->hasRole('reseller')) {
                    $user->removeRole('reseller');
                }
                if ($user->role === 'reseller') {
                    // Jika user ini murni akun reseller (bukan user portal pelanggan aktif), hapus user
                    if (!$this->customer_id || $user->id !== $this->customer?->user_id) {
                        $user->delete();
                    } else {
                        $user->update(['role' => 'customer']);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Reseller::purgeConnectedData] Error updating user: ' . $e->getMessage());
        }
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(ResellerTransaction::class);
    }

    public function balanceRequests()
    {
        return $this->hasMany(ResellerBalanceRequest::class);
    }
}
