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
     * Tentukan karyawan yang berhak menerima reward closing dari pelanggan.
     */
    public static function resolveCustomerEmployee(Customer $customer, ?string $targetRole = null): ?User
    {
        $candidateId = $customer->sales_id;

        // Fallback cerdas jika sales_id tidak terisi
        if (!$candidateId) {
            if ($targetRole === 'teknisi' && !empty($customer->psb_received_by)) {
                $candidateId = $customer->psb_received_by;
            } elseif (!empty($customer->user_id)) {
                $candidateId = $customer->user_id;
            }
        }

        if (!$candidateId) {
            return null;
        }

        $employee = User::find($candidateId);
        if (!$employee || $employee->isExemptFromAttendanceAndPayroll()) {
            return null;
        }

        if (!empty($targetRole) && $targetRole !== 'all' && $employee->role !== $targetRole) {
            return null;
        }

        return $employee;
    }

    /**
     * Hitung dan catat bonus target market untuk sebuah aktivasi pelanggan.
     */
    public static function processCustomerActivation(Customer $customer): void
    {
        if (Customer::$isMigrating) {
            return;
        }

        $activationDate = $customer->activation_date ? Carbon::parse($customer->activation_date) : ($customer->registration_date ? Carbon::parse($customer->registration_date) : now());
        if ($customer->is_migrated && $activationDate->copy()->startOfDay()->lt(Carbon::parse('2026-10-01')->startOfDay())) {
            return;
        }

        $month = $activationDate->month;
        $year = $activationDate->year;

        // Jalankan recalculatePeriod untuk bulan ini agar seluruh urutan kronologis dan target bonus selalu konsisten
        self::recalculatePeriod($month, $year);
    }

    /**
     * Tangani pembatalan, deaktifasi, atau penghapusan pelanggan agar bonus tidak cair fiktif.
     */
    public static function handleCustomerDeactivation(Customer $customer): void
    {
        $logs = TargetMarketLog::where('customer_id', $customer->id)->get();
        if ($logs->isEmpty()) {
            return;
        }

        $periodsToRecalculate = [];

        foreach ($logs as $log) {
            $periodsToRecalculate[$log->period_year . '_' . $log->period_month] = [
                'month' => $log->period_month,
                'year' => $log->period_year,
            ];

            // Jika belum disinkronkan ke payroll atau insentifnya masih pending, hapus log
            if ($log->status !== 'synced_payroll' || !$log->incentive_id) {
                $log->delete();
            } else {
                $inc = Incentive::find($log->incentive_id);
                if ($inc && $inc->status === 'pending') {
                    $log->delete();
                }
            }
        }

        foreach ($periodsToRecalculate as $period) {
            self::recalculatePeriod($period['month'], $period['year']);
        }
    }

    /**
     * Kalkulasi bonus per customer per program dengan urutan kronologis deterministik.
     */
    public static function calculateAndLog(
        TargetMarketProgram $program,
        Customer $customer,
        User $employee,
        int $month,
        int $year,
        ?int $explicitClosingNumber = null
    ): ?TargetMarketLog {
        if ($explicitClosingNumber !== null) {
            $currentClosingNumber = $explicitClosingNumber;
        } else {
            // Hitung urutan kronologis aktivasi pelanggan karyawan ini di periode berjalan
            $custDate = $customer->activation_date ? Carbon::parse($customer->activation_date)->toDateString() : ($customer->created_at ? $customer->created_at->toDateString() : now()->toDateString());

            $priorCount = TargetMarketLog::where('program_id', $program->id)
                ->where('user_id', $employee->id)
                ->where('customer_id', '!=', $customer->id)
                ->whereHas('customer', function ($q) use ($custDate, $customer) {
                    $q->where(function ($sub) use ($custDate, $customer) {
                        $sub->whereDate('activation_date', '<', $custDate)
                            ->orWhere(function ($eq) use ($custDate, $customer) {
                                $eq->whereDate('activation_date', '=', $custDate)
                                   ->where('id', '<', $customer->id);
                            });
                    });
                })
                ->count();

            $currentClosingNumber = $priorCount + 1;
        }

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
                        'closing_seq' => $currentClosingNumber,
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

        // 4. Bonus Pencapaian Target Kuota (Lump Sum Reward saat target tercapai tepat pada urutan ke-target_quota)
        if ($program->reward_bonus_target > 0 && $currentClosingNumber === (int) $program->target_quota) {
            $targetAchievedReward = (float) $program->reward_bonus_target;
            $breakdown['target_achieved'] = [
                'target_quota' => $program->target_quota,
                'lump_sum_bonus' => $targetAchievedReward,
                'closing_seq' => $currentClosingNumber,
            ];
        }

        $totalReward = $baseReward + $tierReward + $areaReward + $packageReward + $targetAchievedReward;

        $existingLog = TargetMarketLog::where('program_id', $program->id)
            ->where('customer_id', $customer->id)
            ->first();

        // Lindungi status synced_payroll agar tidak ditimpa kembali menjadi approved jika sudah tercatat di payroll
        $status = 'approved';
        $incentiveId = null;

        if ($existingLog) {
            if ($existingLog->status === 'synced_payroll') {
                $status = 'synced_payroll';
                $incentiveId = $existingLog->incentive_id;
            }
        }

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
                'status' => $status,
                'incentive_id' => $incentiveId,
            ]
        );

        return $log;
    }

    /**
     * Hitung ulang seluruh data untuk bulan & tahun tertentu secara deterministik & sinkron.
     */
    public static function recalculatePeriod(int $month, int $year): array
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $programs = TargetMarketProgram::where('is_active', true)
            ->where(function ($q) use ($month, $year, $monthStart, $monthEnd) {
                $q->where(function ($sub) use ($monthStart, $monthEnd) {
                    $sub->whereNotNull('start_date')
                        ->where('start_date', '<=', $monthEnd)
                        ->where(function ($endSub) use ($monthStart) {
                            $endSub->whereNull('end_date')
                                   ->orWhere('end_date', '>=', $monthStart);
                        });
                })->orWhere(function ($sub) use ($month, $year) {
                    $sub->whereNull('start_date')
                        ->where('period_month', $month)
                        ->where('period_year', $year);
                });
            })
            ->get();

        // Lindungi log yang insentifnya SUDAH PAID (sudah cair saat payroll).
        // Log yang belum paid (masih pending atau approved) aman untuk dibersihkan dan dihitung ulang.
        $paidIncentiveIds = Incentive::where('status', 'paid')
            ->where('reference_id', 'like', "target_market_%_{$year}_{$month}%")
            ->pluck('id');

        TargetMarketLog::where('period_month', $month)
            ->where('period_year', $year)
            ->where(function ($q) use ($paidIncentiveIds) {
                $q->where('status', '!=', 'synced_payroll')
                  ->orWhereNotIn('incentive_id', $paidIncentiveIds)
                  ->orWhereNull('incentive_id');
            })
            ->delete();

        if ($programs->isEmpty()) {
            // Sinkronkan ke payroll untuk membersihkan sisa pending incentive jika tidak ada program aktif
            self::syncToPayroll($month, $year);
            return ['processed' => 0, 'total_bonus' => 0];
        }

        // Ambil pelanggan berstatus aktif di periode ini
        $customers = Customer::where('status', 'active')
            ->where(function ($q) {
                $q->where('is_migrated', false)
                  ->orWhere(function ($sub) {
                      $sub->where('is_migrated', true)
                          ->whereDate('activation_date', '>=', '2026-10-01');
                  });
            })
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
            ->get();

        $processedCount = 0;
        $totalBonusSum = 0;

        foreach ($programs as $prog) {
            // Saring customer yang memenuhi syarat rentang tanggal & area program
            $eligibleCustomers = $customers->filter(function ($cust) use ($prog) {
                $custDateStr = $cust->activation_date ? Carbon::parse($cust->activation_date)->toDateString() : ($cust->created_at ? $cust->created_at->toDateString() : null);
                if ($custDateStr) {
                    if ($prog->start_date && $custDateStr < Carbon::parse($prog->start_date)->toDateString()) return false;
                    if ($prog->end_date && $custDateStr > Carbon::parse($prog->end_date)->toDateString()) return false;
                }
                if (!empty($prog->target_area_id) && (int) $cust->area_id !== (int) $prog->target_area_id) {
                    return false;
                }
                return true;
            });

            // Kelompokkan per karyawan
            $custsByEmployee = [];
            foreach ($eligibleCustomers as $cust) {
                $employee = self::resolveCustomerEmployee($cust, $prog->target_role);
                if (!$employee) continue;

                $custTimestamp = $cust->activation_date
                    ? Carbon::parse($cust->activation_date)->timestamp
                    : ($cust->created_at ? $cust->created_at->timestamp : 0);

                $custsByEmployee[$employee->id][] = [
                    'customer' => $cust,
                    'employee' => $employee,
                    'timestamp' => $custTimestamp,
                    'id' => $cust->id,
                ];
            }

            // Loop per karyawan dan urutkan secara kronologis untuk kalkulasi urutan closing yang presisi
            foreach ($custsByEmployee as $empId => $items) {
                usort($items, function ($a, $b) {
                    if ($a['timestamp'] === $b['timestamp']) {
                        return $a['id'] <=> $b['id'];
                    }
                    return $a['timestamp'] <=> $b['timestamp'];
                });

                $closingIndex = 1;
                foreach ($items as $item) {
                    $log = self::calculateAndLog($prog, $item['customer'], $item['employee'], $month, $year, $closingIndex);
                    if ($log) {
                        $processedCount++;
                        $totalBonusSum += (float) $log->total_reward;
                    }
                    $closingIndex++;
                }
            }
        }

        // Sinkronkan ke modul Insentif Payroll untuk memperbarui nominal pending
        try {
            self::syncToPayroll($month, $year);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("[TargetMarket Auto-Sync] " . $e->getMessage());
        }

        return [
            'processed' => $processedCount,
            'total_bonus' => $totalBonusSum,
        ];
    }

    /**
     * Sinkronkan akumulasi bonus ke modul Insentif Payroll secara aman.
     */
    public static function syncToPayroll(int $month, int $year): array
    {
        $logs = TargetMarketLog::where('period_month', $month)
            ->where('period_year', $year)
            ->whereIn('status', ['approved', 'synced_payroll'])
            ->get();

        $monthName = Carbon::create($year, $month, 1)->translatedFormat('F Y');
        $date = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        $syncedUsers = 0;
        $totalAmount = 0;

        $userIdsWithLogs = $logs->pluck('user_id')->unique()->filter()->values();
        $existingTargetIncentives = Incentive::where('reference_id', 'like', "target_market_%_{$year}_{$month}")
            ->where('status', 'pending')
            ->get();

        DB::transaction(function () use (
            $logs, $userIdsWithLogs, $existingTargetIncentives,
            $month, $year, $monthName, $date,
            &$syncedUsers, &$totalAmount
        ) {
            $userSums = $logs->groupBy('user_id');

            foreach ($userIdsWithLogs as $userId) {
                $userLogs = $userSums->get($userId, collect());
                $sum = (float) $userLogs->sum('total_reward');
                $count = $userLogs->pluck('customer_id')->unique()->count();

                $user = User::find($userId);
                if (!$user || $user->isExemptFromAttendanceAndPayroll()) continue;

                $refId = "target_market_{$user->id}_{$year}_{$month}";

                // Cek migrasi dari legacy incentive target market tanpa reference_id
                $legacyInc = Incentive::where('user_id', $user->id)
                    ->whereNull('reference_id')
                    ->where('description', 'like', "[Bonus Target Market] Periode {$monthName}%")
                    ->where('status', 'pending')
                    ->first();
                if ($legacyInc) {
                    $legacyInc->update(['reference_id' => $refId]);
                }

                if ($sum > 0) {
                    $description = "[Bonus Target Market] Periode {$monthName} ({$count} Pelanggan Closing)";

                    $existingInc = Incentive::where('user_id', $user->id)
                        ->where('reference_id', $refId)
                        ->first();

                    if ($existingInc && $existingInc->status === 'paid') {
                        $incentive = $existingInc;
                        $remaining = $sum - (float) $existingInc->amount;
                        if ($remaining > 0) {
                            $adjRefId = "target_market_{$user->id}_{$year}_{$month}_extra";
                            $adjDescription = "[Bonus Target Market Tambahan] Periode {$monthName} (+ Rp " . number_format($remaining, 0, ',', '.') . ")";
                            Incentive::updateOrCreate(
                                [
                                    'user_id' => $user->id,
                                    'reference_id' => $adjRefId,
                                ],
                                [
                                    'description' => $adjDescription,
                                    'amount' => $remaining,
                                    'type' => 'auto',
                                    'incentive_date' => now()->toDateString(),
                                    'status' => 'pending',
                                ]
                            );
                        }
                    } else {
                        $incentive = Incentive::updateOrCreate(
                            [
                                'user_id' => $user->id,
                                'reference_id' => $refId,
                            ],
                            [
                                'description' => $description,
                                'amount' => $sum,
                                'type' => 'auto',
                                'incentive_date' => $date,
                                'status' => 'pending',
                            ]
                        );
                    }

                    TargetMarketLog::whereIn('id', $userLogs->pluck('id'))->update([
                        'status' => 'synced_payroll',
                        'incentive_id' => $incentive->id,
                    ]);

                    $syncedUsers++;
                    $totalAmount += $sum;
                } else {
                    Incentive::where('user_id', $user->id)
                        ->where('reference_id', $refId)
                        ->where('status', 'pending')
                        ->delete();
                }
            }

            foreach ($existingTargetIncentives as $oldInc) {
                if (!$userIdsWithLogs->contains($oldInc->user_id)) {
                    $oldInc->delete();
                }
            }
        });

        return [
            'synced_users' => $syncedUsers,
            'total_amount' => $totalAmount,
        ];
    }
}
