<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Incentive;
use App\Models\TargetMarketLog;
use App\Models\TargetMarketProgram;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TargetMarketService
{
    /**
     * Hitung dan catat bonus target market untuk sebuah aktivasi pelanggan.
     */
    public static function processCustomerActivation(Customer $customer): void
    {
        if (!$customer->sales_id) {
            return;
        }

        $activationDate = $customer->activation_date ? Carbon::parse($customer->activation_date) : now();
        $month = $activationDate->month;
        $year = $activationDate->year;

        // Ambil program aktif untuk periode ini
        $programs = TargetMarketProgram::where('is_active', true)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->get();

        if ($programs->isEmpty()) {
            return;
        }

        $employee = User::find($customer->sales_id);
        if (!$employee) {
            return;
        }

        foreach ($programs as $program) {
            // Filter role jika ditentukan
            if (!empty($program->target_role) && $program->target_role !== 'all' && $employee->role !== $program->target_role) {
                continue;
            }

            self::calculateAndLog($program, $customer, $employee, $month, $year);
        }
    }

    /**
     * Kalkulasi bonus per customer per program.
     */
    public static function calculateAndLog(TargetMarketProgram $program, Customer $customer, User $employee, int $month, int $year): ?TargetMarketLog
    {
        // Hitung urutan closing karyawan ini di periode berjalan
        $previousCount = TargetMarketLog::where('program_id', $program->id)
            ->where('user_id', $employee->id)
            ->where('customer_id', '!=', $customer->id)
            ->count();

        $currentClosingNumber = $previousCount + 1;

        $baseReward = (float) $program->reward_flat;
        $tierReward = 0.0;
        $areaReward = 0.0;
        $packageReward = 0.0;
        $targetAchievedReward = 0.0;
        $breakdown = [];

        $rules = $program->rules ?? [];

        // 1. Konsep Tier / Milestone Volume
        if (in_array($program->concept, ['milestone_volume', 'hybrid']) && !empty($rules['tiers'])) {
            foreach ($rules['tiers'] as $tier) {
                $min = (int) ($tier['min'] ?? 1);
                $max = !empty($tier['max']) ? (int) $tier['max'] : PHP_INT_MAX;
                if ($currentClosingNumber >= $min && $currentClosingNumber <= $max) {
                    $tierReward = (float) ($tier['reward'] ?? 0);
                    $breakdown['tier'] = [
                        'tier_name' => "Tier ($min - " . ($max === PHP_INT_MAX ? 'keatas' : $max) . ")",
                        'reward' => $tierReward,
                    ];
                    break;
                }
            }
        }

        // 2. Konsep Penetrasi Area / ODP Target
        if (in_array($program->concept, ['area_expansion', 'hybrid']) && !empty($rules['target_areas'])) {
            foreach ($rules['target_areas'] as $tArea) {
                if ((int) ($tArea['area_id'] ?? 0) === (int) $customer->area_id) {
                    $areaReward = (float) ($tArea['bonus'] ?? 0);
                    $breakdown['area'] = [
                        'area_name' => $customer->areaModel?->name ?? 'Target Area',
                        'bonus' => $areaReward,
                    ];
                    break;
                }
            }
        }

        // 3. Konsep Nilai Paket / Revenue
        if (in_array($program->concept, ['package_tier', 'hybrid']) && !empty($rules['target_packages'])) {
            foreach ($rules['target_packages'] as $tPkg) {
                if ((int) ($tPkg['package_id'] ?? 0) === (int) $customer->package_id) {
                    $packageReward = (float) ($tPkg['bonus'] ?? 0);
                    $breakdown['package'] = [
                        'package_name' => $customer->package?->name ?? 'Target Package',
                        'bonus' => $packageReward,
                    ];
                    break;
                }
            }
        }

        // 4. Bonus Pencapaian Target Kuota (Lump Sum Reward saat target tercapai)
        if ($program->reward_bonus_target > 0 && $currentClosingNumber === (int) $program->target_quota) {
            $targetAchievedReward = (float) $program->reward_bonus_target;
            $breakdown['target_achieved'] = [
                'target_quota' => $program->target_quota,
                'lump_sum_bonus' => $targetAchievedReward,
            ];
        }

        $totalReward = $baseReward + $tierReward + $areaReward + $packageReward + $targetAchievedReward;

        $log = TargetMarketLog::updateOrCreate(
            [
                'program_id' => $program->id,
                'customer_id' => $customer->id,
            ],
            [
                'user_id' => $employee->id,
                'base_reward' => $baseReward,
                'tier_reward' => $tierReward,
                'area_reward' => $areaReward,
                'package_reward' => $packageReward,
                'target_achieved_reward' => $targetAchievedReward,
                'total_reward' => $totalReward,
                'concept_breakdown' => $breakdown,
                'period_month' => $month,
                'period_year' => $year,
                'status' => 'approved',
            ]
        );

        return $log;
    }

    /**
     * Hitung ulang seluruh data untuk bulan & tahun tertentu.
     */
    public static function recalculatePeriod(int $month, int $year): array
    {
        // Hapus log lama yang belum synced payroll untuk periode ini terlebih dahulu
        TargetMarketLog::where('period_month', $month)
            ->where('period_year', $year)
            ->where('status', '!=', 'synced_payroll')
            ->delete();

        $programs = TargetMarketProgram::where('is_active', true)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->get();

        if ($programs->isEmpty()) {
            return ['processed' => 0, 'total_bonus' => 0];
        }

        // Cari pelanggan aktif di periode ini yang memiliki sales_id
        $customers = Customer::where('status', 'active')
            ->whereNotNull('sales_id')
            ->where(function ($q) use ($month, $year) {
                $q->where(function ($sub) use ($month, $year) {
                    $sub->whereNotNull('activation_date')
                        ->whereMonth('activation_date', $month)
                        ->whereYear('activation_date', $year);
                })->orWhere(function ($sub) use ($month, $year) {
                    $sub->whereNull('activation_date')
                        ->whereMonth('created_at', $month)
                        ->whereYear('created_at', $year);
                });
            })
            ->with(['package', 'areaModel', 'sales'])
            ->orderBy('activation_date')
            ->orderBy('id')
            ->get();

        $processedCount = 0;
        $totalBonusSum = 0;

        foreach ($customers as $cust) {
            $employee = $cust->sales;
            if (!$employee) continue;

            foreach ($programs as $prog) {
                if (!empty($prog->target_role) && $prog->target_role !== 'all' && $employee->role !== $prog->target_role) {
                    continue;
                }

                $log = self::calculateAndLog($prog, $cust, $employee, $month, $year);
                if ($log) {
                    $processedCount++;
                    $totalBonusSum += (float) $log->total_reward;
                }
            }
        }

        return [
            'processed' => $processedCount,
            'total_bonus' => $totalBonusSum,
        ];
    }

    /**
     * Sinkronkan akumulasi bonus ke modul Insentif Payroll.
     */
    public static function syncToPayroll(int $month, int $year): array
    {
        $logs = TargetMarketLog::where('period_month', $month)
            ->where('period_year', $year)
            ->where('status', 'approved')
            ->get();

        if ($logs->isEmpty()) {
            return ['synced_users' => 0, 'total_amount' => 0];
        }

        $userSums = $logs->groupBy('user_id');
        $syncedUsers = 0;
        $totalAmount = 0;

        DB::transaction(function () use ($userSums, $month, $year, &$syncedUsers, &$totalAmount) {
            $monthName = Carbon::create($year, $month, 1)->translatedFormat('F Y');
            $date = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

            foreach ($userSums as $userId => $userLogs) {
                $sum = $userLogs->sum('total_reward');
                $count = $userLogs->count();

                if ($sum <= 0) continue;

                $user = User::find($userId);
                if (!$user) continue;

                $description = "[Bonus Target Market] Periode {$monthName} ({$count} Pelanggan Closing)";

                // Update or create incentive
                $incentive = Incentive::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'description' => $description,
                    ],
                    [
                        'amount' => $sum,
                        'type' => 'auto',
                        'incentive_date' => $date,
                        'status' => 'pending',
                    ]
                );

                // Tandai logs sebagai synced
                TargetMarketLog::whereIn('id', $userLogs->pluck('id'))->update([
                    'status' => 'synced_payroll',
                    'incentive_id' => $incentive->id,
                ]);

                $syncedUsers++;
                $totalAmount += $sum;
            }
        });

        return [
            'synced_users' => $syncedUsers,
            'total_amount' => $totalAmount,
        ];
    }
}
