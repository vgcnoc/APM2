<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Customer;
use App\Models\Incentive;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Sesuaikan incentive_date untuk seluruh insentif operasional (Fee Booking, Fee Survey, Fee Pasang)
     * yang pelanggannya aktif pada bulan Oktober 2026, agar otomatis tercatat dan tampil pada
     * periode penggajian bulan Oktober di halaman Payroll (/payroll?month=10&year=2026).
     */
    public function up(): void
    {
        $pending = Incentive::where('status', 'pending')
            ->where(function($q) {
                $q->where('reference_id', 'like', 'booking_cust_%')
                  ->orWhere('reference_id', 'like', 'survey_cust_%')
                  ->orWhere('reference_id', 'like', 'install_cust_%');
            })
            ->get();

        foreach ($pending as $inc) {
            if (preg_match('/cust_(\d+)/i', $inc->reference_id ?? '', $m)) {
                $custId = (int) $m[1];
                $cust = Customer::find($custId);
                if ($cust) {
                    $actDate = $cust->activation_date ?: $cust->registration_date;
                    if ($actDate) {
                        $cDate = Carbon::parse($actDate);
                        if ($cDate->year === 2026 && $cDate->month === 10) {
                            $inc->update([
                                'incentive_date' => $cDate->format('Y-m-d H:i:s'),
                            ]);
                        }
                    }
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

