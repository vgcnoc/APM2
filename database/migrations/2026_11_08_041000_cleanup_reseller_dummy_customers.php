<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Reseller;
use App\Models\Customer;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Lepaskan relasi customer dummy dari tabel resellers
        $dummyResellers = Reseller::whereNotNull('customer_id')
            ->whereHas('customer', function ($q) {
                $q->whereNull('package_id')
                  ->where('is_reseller', true)
                  ->where(function ($sq) {
                      $sq->where('notes', 'like', '%Akun Master Biller dibuat via Pengaturan Reseller%')
                        ->orWhere('notes', 'like', '%Akun Mitra Reseller dibuat via Pengaturan Reseller%')
                        ->orWhere('notes', 'like', '%Pendaftaran Mandiri%');
                  });
            })->get();

        foreach ($dummyResellers as $r) {
            $custId = $r->customer_id;
            $r->update(['customer_id' => null]);

            if ($custId) {
                $cust = Customer::find($custId);
                if ($cust && $cust->invoices()->count() === 0 && !$cust->ont && $cust->surveys()->count() === 0) {
                    $cust->delete();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};

