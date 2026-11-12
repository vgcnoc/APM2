<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Customer;
use App\Models\Incentive;
use App\Models\MasterFee;
use App\Models\User;
use App\Models\AffiliateCommission;
use App\Services\AffiliateService;

return new class extends Migration
{
    /**
     * Bentuk Fee Booking (Rp 35.000), Fee Survey (Rp 15.000), dan Fee Pasang (Rp 35.000)
     * untuk pelanggan hasil migrasi yang aktif mulai 1 Oktober 2026 yang belum memiliki insentif,
     * agar siap dicairkan pada periode penggajian bulan November ini.
     */
    public function up(): void
    {
        $masterBooking = (float) (MasterFee::where('type', 'Fee Booking')->where('is_active', true)->value('nominal') ?? 35000);
        $masterSurvey = (float) (MasterFee::where('type', 'Fee Survey')->where('is_active', true)->value('nominal') ?? 15000);
        $masterPasang = (float) (MasterFee::where('type', 'Fee Pasang')->where('is_active', true)->value('nominal') ?? 35000);

        // Ambil semua pelanggan aktif yang registrasi/aktivasi mulai 1 Oktober 2026
        $customers = Customer::where('status', 'active')
            ->where(function($q) {
                $q->where('activation_date', '>=', '2026-10-01')
                  ->orWhere(function($sub) {
                      $sub->whereNull('activation_date')
                          ->where('registration_date', '>=', '2026-10-01');
                  });
            })
            ->with(['sales', 'surveys', 'technicianSchedules'])
            ->get();

        foreach ($customers as $customer) {
            if ($customer->isPureReseller() || in_array($customer->service_status, ['gratis', 'free_home'])) {
                continue;
            }

            $codeLabel = ($customer->customer_code) ? " ({$customer->customer_code})" : '';

            $targetIncentiveDate = $customer->activation_date ?: ($customer->registration_date ?: now());

            // 1. Fee Booking (Rp 35.000) ke Sales Karyawan
            if ($customer->sales_id) {
                $salesUser = User::find($customer->sales_id);
                if ($salesUser && AffiliateService::isInternalEmployee($salesUser)) {
                    $bookingAmount = ($salesUser->booking_fee && (float)$salesUser->booking_fee > 0)
                        ? (float) $salesUser->booking_fee
                        : $masterBooking;

                    if ($bookingAmount > 0) {
                        $bookingRef = 'booking_cust_' . $customer->id;
                        $alreadyBooking = Incentive::where('reference_id', $bookingRef)->exists();

                        if (!$alreadyBooking) {
                            Incentive::create([
                                'user_id' => $salesUser->id,
                                'amount' => $bookingAmount,
                                'type' => 'auto',
                                'description' => "Fee Booking Pelanggan: {$customer->name}{$codeLabel}",
                                'reference_id' => $bookingRef,
                                'incentive_date' => $targetIncentiveDate,
                                'status' => 'pending',
                            ]);

                            $alreadyComm = AffiliateCommission::where('affiliate_id', $salesUser->id)
                                ->where('customer_id', $customer->id)
                                ->where('type', 'booking')
                                ->exists();

                            if (!$alreadyComm) {
                                AffiliateCommission::create([
                                    'affiliate_id' => $salesUser->id,
                                    'customer_id' => $customer->id,
                                    'source_user_id' => $salesUser->id,
                                    'type' => 'booking',
                                    'tier_level' => 0,
                                    'amount' => $bookingAmount,
                                    'description' => "Fee Booking Pelanggan: {$customer->name}{$codeLabel} [Masuk Insentif Payroll]",
                                    'status' => 'payroll',
                                    'paid_at' => null,
                                ]);
                            }
                        }
                    }
                }
            }

            // 2. Fee Survey (Rp 15.000) ke Teknisi Surveyor Karyawan
            $surveyorIds = collect();
            foreach ($customer->surveys as $survey) {
                if ($survey->surveyor_id) $surveyorIds->push($survey->surveyor_id);
            }
            foreach ($customer->technicianSchedules->where('type', 'survey') as $sched) {
                if ($sched->technician_id) $surveyorIds->push($sched->technician_id);
            }
            $surveyorIds = $surveyorIds->unique()->filter()->values();

            foreach ($surveyorIds as $srvId) {
                $surveyorUser = User::find($srvId);
                if (!$surveyorUser || !AffiliateService::isInternalEmployee($surveyorUser)) continue;

                if ($masterSurvey > 0) {
                    $surveyRef = 'survey_cust_' . $customer->id . '_user_' . $surveyorUser->id;
                    $alreadySurvey = Incentive::where('reference_id', $surveyRef)->exists();

                    if (!$alreadySurvey) {
                        Incentive::create([
                            'user_id' => $surveyorUser->id,
                            'amount' => $masterSurvey,
                            'type' => 'auto',
                            'description' => "Fee Survey Pelanggan: {$customer->name}{$codeLabel}",
                            'reference_id' => $surveyRef,
                            'incentive_date' => $targetIncentiveDate,
                            'status' => 'pending',
                        ]);
                    }
                }
            }

            // 3. Fee Pasang (Rp 35.000) ke Teknisi Instalasi Karyawan
            $techIds = collect();
            foreach ($customer->technicianSchedules->where('type', 'installation') as $ti) {
                if ($ti->technician_id) $techIds->push($ti->technician_id);
            }
            $techIds = $techIds->unique()->filter()->values();

            foreach ($techIds as $tId) {
                $techUser = User::find($tId);
                if (!$techUser || !AffiliateService::isInternalEmployee($techUser)) continue;

                $installAmount = ($techUser->installation_fee && (float)$techUser->installation_fee > 0)
                    ? (float) $techUser->installation_fee
                    : $masterPasang;

                if ($installAmount > 0) {
                    $installRef = 'install_cust_' . $customer->id . '_user_' . $techUser->id;
                    $alreadyInstall = Incentive::where('reference_id', $installRef)->exists();

                    if (!$alreadyInstall) {
                        Incentive::create([
                            'user_id' => $techUser->id,
                            'amount' => $installAmount,
                            'type' => 'auto',
                            'description' => "Fee Pasang Pelanggan: {$customer->name}{$codeLabel}",
                            'reference_id' => $installRef,
                            'incentive_date' => $targetIncentiveDate,
                            'status' => 'pending',
                        ]);
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

