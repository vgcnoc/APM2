<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Payroll;
use App\Models\Incentive;
use App\Models\Expense;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    /**
     * Hitung rincian BPJS Kesehatan & Ketenagakerjaan untuk seorang karyawan.
     */
    public static function calculateBpjs(User $user, float $baseSalary): array
    {
        $settings = [
            'bpjs_kesehatan_employee_pct' => (float) Setting::get('bpjs_kesehatan_employee_pct', 1.0),
            'bpjs_kesehatan_company_pct' => (float) Setting::get('bpjs_kesehatan_company_pct', 4.0),
            'bpjs_tk_jht_employee_pct' => (float) Setting::get('bpjs_tk_jht_employee_pct', 2.0),
            'bpjs_tk_jht_company_pct' => (float) Setting::get('bpjs_tk_jht_company_pct', 3.7),
            'bpjs_tk_jp_employee_pct' => (float) Setting::get('bpjs_tk_jp_employee_pct', 1.0),
            'bpjs_tk_jp_company_pct' => (float) Setting::get('bpjs_tk_jp_company_pct', 2.0),
            'bpjs_tk_jkk_company_pct' => (float) Setting::get('bpjs_tk_jkk_company_pct', 0.24),
            'bpjs_tk_jkm_company_pct' => (float) Setting::get('bpjs_tk_jkm_company_pct', 0.30),
        ];

        // 1. BPJS Kesehatan (Batas atas upah: 12.000.000 jika disetel)
        $kesehatanEmployee = 0;
        $kesehatanCompany = 0;
        if ($user->bpjs_kesehatan && $baseSalary > 0) {
            $capKesehatan = (float) Setting::get('bpjs_cap_kesehatan', 12000000);
            $wageKesehatan = ($capKesehatan > 0) ? min($baseSalary, $capKesehatan) : $baseSalary;
            $kesehatanEmployee = round(($settings['bpjs_kesehatan_employee_pct'] / 100) * $wageKesehatan);
            $kesehatanCompany = round(($settings['bpjs_kesehatan_company_pct'] / 100) * $wageKesehatan);
        }

        // 2. BPJS Ketenagakerjaan (JHT, JP, JKK, JKM)
        $tkJhtEmployee = 0;
        $tkJhtCompany = 0;
        $tkJpEmployee = 0;
        $tkJpCompany = 0;
        $tkJkkCompany = 0;
        $tkJkmCompany = 0;

        if ($user->bpjs_ketenagakerjaan && $baseSalary > 0) {
            // JHT: 2% Karyawan, 3.7% Perusahaan
            $tkJhtEmployee = round(($settings['bpjs_tk_jht_employee_pct'] / 100) * $baseSalary);
            $tkJhtCompany = round(($settings['bpjs_tk_jht_company_pct'] / 100) * $baseSalary);

            // JP: 1% Karyawan, 2% Perusahaan (Batas atas upah JP: 10.042.300)
            $capJp = (float) Setting::get('bpjs_cap_jp', 10042300);
            $wageJp = ($capJp > 0) ? min($baseSalary, $capJp) : $baseSalary;
            $tkJpEmployee = round(($settings['bpjs_tk_jp_employee_pct'] / 100) * $wageJp);
            $tkJpCompany = round(($settings['bpjs_tk_jp_company_pct'] / 100) * $wageJp);

            // JKK & JKM: 0.24% & 0.3% Ditanggung Perusahaan (Karyawan 0%)
            $tkJkkCompany = round(($settings['bpjs_tk_jkk_company_pct'] / 100) * $baseSalary);
            $tkJkmCompany = round(($settings['bpjs_tk_jkm_company_pct'] / 100) * $baseSalary);
        }

        $totalEmployee = $kesehatanEmployee + $tkJhtEmployee + $tkJpEmployee;
        $totalCompany = $kesehatanCompany + $tkJhtCompany + $tkJpCompany + $tkJkkCompany + $tkJkmCompany;

        return [
            'has_kesehatan' => (bool) $user->bpjs_kesehatan,
            'has_tk' => (bool) $user->bpjs_ketenagakerjaan,
            'kesehatan_no' => $user->bpjs_kesehatan_no,
            'tk_no' => $user->bpjs_tk_no,
            'kesehatan_employee' => (float) $kesehatanEmployee,
            'kesehatan_company' => (float) $kesehatanCompany,
            'tk_jht_employee' => (float) $tkJhtEmployee,
            'tk_jht_company' => (float) $tkJhtCompany,
            'tk_jp_employee' => (float) $tkJpEmployee,
            'tk_jp_company' => (float) $tkJpCompany,
            'tk_jkk_company' => (float) $tkJkkCompany,
            'tk_jkm_company' => (float) $tkJkmCompany,
            'total_employee' => (float) $totalEmployee,
            'total_company' => (float) $totalCompany,
            'grand_total_bpjs' => (float) ($totalEmployee + $totalCompany),
        ];
    }

    /**
     * Dashboard Penggajian & Insentif
     */
    public function index(Request $request)
    {
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);
        
        // Pengaturan Tanggal Pencairan & Tarif BPJS
        $disbursementDate = Setting::get('payroll_disbursement_date', '25'); // Default tanggal 25
        $bpjsSettings = [
            'bpjs_kesehatan_employee_pct' => (float) Setting::get('bpjs_kesehatan_employee_pct', 1.0),
            'bpjs_kesehatan_company_pct' => (float) Setting::get('bpjs_kesehatan_company_pct', 4.0),
            'bpjs_tk_jht_employee_pct' => (float) Setting::get('bpjs_tk_jht_employee_pct', 2.0),
            'bpjs_tk_jht_company_pct' => (float) Setting::get('bpjs_tk_jht_company_pct', 3.7),
            'bpjs_tk_jp_employee_pct' => (float) Setting::get('bpjs_tk_jp_employee_pct', 1.0),
            'bpjs_tk_jp_company_pct' => (float) Setting::get('bpjs_tk_jp_company_pct', 2.0),
            'bpjs_tk_jkk_company_pct' => (float) Setting::get('bpjs_tk_jkk_company_pct', 0.24),
            'bpjs_tk_jkm_company_pct' => (float) Setting::get('bpjs_tk_jkm_company_pct', 0.30),
        ];

        $recapBpjs = [
            'total_kesehatan_employee' => 0,
            'total_kesehatan_company' => 0,
            'total_tk_employee' => 0,
            'total_tk_company' => 0,
            'total_deducted_employee' => 0,
            'total_contributed_company' => 0,
            'grand_total_payable' => 0,
            'enrolled_kesehatan_count' => 0,
            'enrolled_tk_count' => 0,
        ];

        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->is_admin || $authUser->hasRole('admin'));

        $excludedRoles = ['customer', 'pelanggan', 'reseller', 'superadmin', 'super admin'];

        $usersQuery = User::where('is_active', true)
            ->whereNotIn('role', $excludedRoles)
            ->whereDoesntHave('roles', function($q) use ($excludedRoles) {
                $q->whereIn('name', $excludedRoles);
            })
            ->whereDoesntHave('customer')
            ->whereDoesntHave('reseller')
            ->where(function($q) {
                $q->where('role', '!=', 'admin')
                  ->orWhere(function($sub) {
                      $sub->where('name', 'not like', '%super admin%')
                          ->where('name', 'not like', '%superadmin%')
                          ->where('email', 'not like', '%superadmin%');
                  });
            })
            ->with([
                'incentives' => function($q) use ($month, $year) {
                    $q->where(function($sub) use ($month, $year) {
                        $sub->where(function($dateQ) use ($month, $year) {
                            $dateQ->whereMonth('incentive_date', $month)->whereYear('incentive_date', $year);
                        })->orWhereHas('payroll', function($pq) use ($month, $year) {
                            $pq->where('month', $month)->where('year', $year);
                        });
                    });
                }, 
                'deductions' => function($q) use ($month, $year) {
                    $q->where(function($sub) use ($month, $year) {
                        $sub->where(function($dateQ) use ($month, $year) {
                            $dateQ->whereMonth('deduction_date', $month)->whereYear('deduction_date', $year);
                        })->orWhereHas('payroll', function($pq) use ($month, $year) {
                            $pq->where('month', $month)->where('year', $year);
                        });
                    });
                },
                'payrolls' => function($q) use ($month, $year) {
                    $q->where('month', $month)->where('year', $year);
                }
            ]);

        if (!$isSuperAdmin && !$authUser->can('payroll_view_all')) {
            if ($authUser->can('payroll_view_area') && $authUser->area_id) {
                $usersQuery->where('area_id', $authUser->area_id);
            } else {
                $usersQuery->where('id', $authUser->id);
            }
        }

        $users = $usersQuery->get()
            ->map(function ($user) use (&$recapBpjs) {
                $payroll = $user->payrolls->first();
                $isPaid = $payroll && $payroll->status === 'paid';

                $rawBaseSalary = (float) $user->base_salary;
                $emp = $user->employee ?: \App\Models\Employee::where('email', $user->email)
                    ->orWhere('name', $user->name)
                    ->orWhere(function($q) use ($user) {
                        if (!empty($user->phone)) {
                            $q->where('phone', $user->phone);
                        }
                    })
                    ->first();
                if ($rawBaseSalary <= 0) {
                    if ($emp && (float) $emp->base_salary > 0) {
                        $rawBaseSalary = (float) $emp->base_salary;
                        $user->update(['base_salary' => $rawBaseSalary]);
                    }
                } elseif ($emp && (float) $emp->base_salary <= 0) {
                    $emp->update(['base_salary' => $rawBaseSalary]);
                }

                $baseSalary = $isPaid ? (float) $payroll->base_salary : $rawBaseSalary;

                if ($isPaid) {
                    $relevantIncentives = $user->incentives->filter(function ($inc) use ($payroll) {
                        return $inc->payroll_id == $payroll->id || $inc->status === 'paid';
                    });
                    if ($relevantIncentives->isNotEmpty()) {
                        $totalManual = (float) $relevantIncentives->where('type', 'manual')->sum('amount');
                        $totalAuto = (float) $relevantIncentives->where('type', '!=', 'manual')->sum('amount');
                    } else {
                        $totalAuto = (float) $payroll->total_incentive;
                        $totalManual = 0;
                    }
                    $totalIncentive = (float) $payroll->total_incentive;

                    $relevantDeductions = $user->deductions->filter(function ($ded) use ($payroll) {
                        return $ded->payroll_id == $payroll->id || $ded->status === 'applied';
                    });
                    $finalDeduction = (float) $payroll->total_deduction;
                } else {
                    $pendingIncentives = $user->incentives->where('status', 'pending');
                    $totalManual = (float) $pendingIncentives->where('type', 'manual')->sum('amount');
                    $totalAuto = (float) $pendingIncentives->where('type', '!=', 'manual')->sum('amount');
                    $totalIncentive = $totalAuto + $totalManual;

                    $pendingDeductions = $user->deductions->where('status', 'pending');
                    $finalDeduction = (float) $pendingDeductions->sum('amount');
                    $relevantIncentives = $pendingIncentives;
                    $relevantDeductions = $pendingDeductions;
                }

                // Hitung / Ambil rincian BPJS
                if ($isPaid) {
                    $bpjs = [
                        'has_kesehatan' => (bool) $user->bpjs_kesehatan,
                        'has_tk' => (bool) $user->bpjs_ketenagakerjaan,
                        'kesehatan_no' => $user->bpjs_kesehatan_no,
                        'tk_no' => $user->bpjs_tk_no,
                        'kesehatan_employee' => (float) ($payroll->bpjs_kesehatan_employee ?? 0),
                        'kesehatan_company' => (float) ($payroll->bpjs_kesehatan_company ?? 0),
                        'tk_jht_employee' => (float) ($payroll->bpjs_tk_jht_employee ?? 0),
                        'tk_jht_company' => (float) ($payroll->bpjs_tk_jht_company ?? 0),
                        'tk_jp_employee' => (float) ($payroll->bpjs_tk_jp_employee ?? 0),
                        'tk_jp_company' => (float) ($payroll->bpjs_tk_jp_company ?? 0),
                        'tk_jkk_company' => (float) ($payroll->bpjs_tk_jkk_company ?? 0),
                        'tk_jkm_company' => (float) ($payroll->bpjs_tk_jkm_company ?? 0),
                        'total_employee' => (float) ($payroll->total_bpjs_employee ?? 0),
                        'total_company' => (float) ($payroll->total_bpjs_company ?? 0),
                        'grand_total_bpjs' => (float) (($payroll->total_bpjs_employee ?? 0) + ($payroll->total_bpjs_company ?? 0)),
                    ];
                    $netSalary = (float) $payroll->net_salary;
                } else {
                    $bpjs = self::calculateBpjs($user, $baseSalary);
                    $netSalary = max(0, $baseSalary + $totalIncentive - $finalDeduction - $bpjs['total_employee']);
                }

                // Akumulasi rekap
                if ($user->bpjs_kesehatan) {
                    $recapBpjs['enrolled_kesehatan_count']++;
                    $recapBpjs['total_kesehatan_employee'] += $bpjs['kesehatan_employee'];
                    $recapBpjs['total_kesehatan_company'] += $bpjs['kesehatan_company'];
                }
                if ($user->bpjs_ketenagakerjaan) {
                    $recapBpjs['enrolled_tk_count']++;
                    $recapBpjs['total_tk_employee'] += ($bpjs['tk_jht_employee'] + $bpjs['tk_jp_employee']);
                    $recapBpjs['total_tk_company'] += ($bpjs['tk_jht_company'] + $bpjs['tk_jp_company'] + $bpjs['tk_jkk_company'] + $bpjs['tk_jkm_company']);
                }
                $recapBpjs['total_deducted_employee'] += $bpjs['total_employee'];
                $recapBpjs['total_contributed_company'] += $bpjs['total_company'];
                $recapBpjs['grand_total_payable'] += $bpjs['grand_total_bpjs'];

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'base_salary' => $baseSalary,
                    'auto_incentive_rate' => (float) $user->incentive_rate,
                    'total_auto_incentive' => $totalAuto,
                    'total_manual_incentive' => $totalManual,
                    'total_incentive' => $totalIncentive,
                    'total_deduction' => $finalDeduction,
                    'bpjs' => $bpjs,
                    'net_salary' => $netSalary,
                    'is_paid' => $isPaid,
                    'payment_date' => $isPaid ? $payroll->payment_date : null,
                    'bank_name' => $user->bank_name,
                    'bank_account_number' => $user->bank_account_number,
                    'bank_account_holder' => $user->bank_account_holder ?: $user->name,
                    'bpjs_kesehatan' => (bool) $user->bpjs_kesehatan,
                    'bpjs_kesehatan_no' => $user->bpjs_kesehatan_no,
                    'bpjs_ketenagakerjaan' => (bool) $user->bpjs_ketenagakerjaan,
                    'bpjs_tk_no' => $user->bpjs_tk_no,
                    'disbursement_channel' => $payroll ? ($payroll->disbursement_channel ?? 'manual') : 'manual',
                    'disbursement_id' => $payroll ? ($payroll->disbursement_id ?? null) : null,
                    'disbursement_status' => $payroll ? ($payroll->disbursement_status ?? null) : null,
                    'disbursement_error' => $payroll ? ($payroll->disbursement_error ?? null) : null,
                    'incentives_list' => $relevantIncentives->values()->toArray(),
                    'deductions_list' => $relevantDeductions->values()->toArray()
                ];
            });

        $categories = \App\Models\PayrollCategory::where('is_active', true)->get();
        $xenditService = new \App\Services\XenditDisbursementService();
        $flipService = new \App\Services\FlipDisbursementService();

        $activeProvider = Setting::get('active_disbursement_provider', 'none');
        if ($activeProvider === 'none') {
            if ($flipService->isEnabled()) {
                $activeProvider = 'flip';
            } elseif ($xenditService->isEnabled()) {
                $activeProvider = 'xendit';
            }
        }

        return Inertia::render('Payroll/Index', [
            'month' => $month,
            'year' => $year,
            'users' => $users,
            'settings' => [
                'disbursement_date' => $disbursementDate,
                ...$bpjsSettings,
            ],
            'activeProvider' => $activeProvider,
            'flipConfig' => $flipService->getConfig(),
            'xenditConfig' => $xenditService->getConfig(),
            'disbursementConfig' => [
                'active_provider' => $activeProvider,
                'flip' => $flipService->getConfig(),
                'xendit' => $xenditService->getConfig(),
            ],
            'recapBpjs' => $recapBpjs,
            'payrollCategories' => $categories
        ]);
    }

    /**
     * Update Pengaturan Tanggal Pencairan & Tarif BPJS
     */
    public function updateSettings(Request $request)
    {
        $authUser = auth()->user();
        if (!$authUser->is_admin && !$authUser->hasRole('admin') && !$authUser->can('payroll_btn_settings_bpjs')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah pengaturan payroll & BPJS.');
        }

        $request->validate([
            'disbursement_date' => 'nullable|integer|min:1|max:31',
            'bpjs_kesehatan_employee_pct' => 'nullable|numeric|min:0|max:100',
            'bpjs_kesehatan_company_pct' => 'nullable|numeric|min:0|max:100',
            'bpjs_tk_jht_employee_pct' => 'nullable|numeric|min:0|max:100',
            'bpjs_tk_jht_company_pct' => 'nullable|numeric|min:0|max:100',
            'bpjs_tk_jp_employee_pct' => 'nullable|numeric|min:0|max:100',
            'bpjs_tk_jp_company_pct' => 'nullable|numeric|min:0|max:100',
            'bpjs_tk_jkk_company_pct' => 'nullable|numeric|min:0|max:100',
            'bpjs_tk_jkm_company_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($request->has('disbursement_date')) {
            Setting::set('payroll_disbursement_date', $request->disbursement_date);
        }
        if ($request->has('bpjs_kesehatan_employee_pct')) {
            Setting::set('bpjs_kesehatan_employee_pct', $request->bpjs_kesehatan_employee_pct);
        }
        if ($request->has('bpjs_kesehatan_company_pct')) {
            Setting::set('bpjs_kesehatan_company_pct', $request->bpjs_kesehatan_company_pct);
        }
        if ($request->has('bpjs_tk_jht_employee_pct')) {
            Setting::set('bpjs_tk_jht_employee_pct', $request->bpjs_tk_jht_employee_pct);
        }
        if ($request->has('bpjs_tk_jht_company_pct')) {
            Setting::set('bpjs_tk_jht_company_pct', $request->bpjs_tk_jht_company_pct);
        }
        if ($request->has('bpjs_tk_jp_employee_pct')) {
            Setting::set('bpjs_tk_jp_employee_pct', $request->bpjs_tk_jp_employee_pct);
        }
        if ($request->has('bpjs_tk_jp_company_pct')) {
            Setting::set('bpjs_tk_jp_company_pct', $request->bpjs_tk_jp_company_pct);
        }
        if ($request->has('bpjs_tk_jkk_company_pct')) {
            Setting::set('bpjs_tk_jkk_company_pct', $request->bpjs_tk_jkk_company_pct);
        }
        if ($request->has('bpjs_tk_jkm_company_pct')) {
            Setting::set('bpjs_tk_jkm_company_pct', $request->bpjs_tk_jkm_company_pct);
        }

        // Xendit Auto Transfer Settings
        if ($request->has('xendit_disbursement_enabled')) {
            Setting::set('xendit_disbursement_enabled', $request->boolean('xendit_disbursement_enabled') ? '1' : '0');
        }
        if ($request->has('xendit_secret_key')) {
            Setting::set('xendit_secret_key', $request->xendit_secret_key ?? '');
        }
        if ($request->has('xendit_webhook_token')) {
            Setting::set('xendit_webhook_token', $request->xendit_webhook_token ?? '');
        }
        if ($request->has('xendit_auto_disburse_on_approve')) {
            Setting::set('xendit_auto_disburse_on_approve', $request->boolean('xendit_auto_disburse_on_approve') ? '1' : '0');
        }

        return back()->with('success', 'Pengaturan penggajian & tarif BPJS berhasil disimpan.');
    }

    /**
     * Update Pengaturan BPJS & Bank Rekening per Karyawan
     */
    public function updateBpjsBank(Request $request, User $user)
    {
        $authUser = auth()->user();
        if (!$authUser->is_admin && !$authUser->hasRole('admin') && !$authUser->can('payroll_btn_settings_bpjs') && !$authUser->can('payroll_manage') && !$authUser->can('payroll_btn_update_bpjs_bank')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah data Gaji, BPJS & Bank.');
        }

        $validated = $request->validate([
            'base_salary' => 'nullable|numeric|min:0',
            'incentive_rate' => 'nullable|numeric|min:0',
            'bpjs_kesehatan' => 'nullable|boolean',
            'bpjs_kesehatan_no' => 'nullable|string|max:50',
            'bpjs_ketenagakerjaan' => 'nullable|boolean',
            'bpjs_tk_no' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:50',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:100',
        ]);

        $updateData = [
            'bpjs_kesehatan' => $request->boolean('bpjs_kesehatan'),
            'bpjs_kesehatan_no' => $request->input('bpjs_kesehatan_no'),
            'bpjs_ketenagakerjaan' => $request->boolean('bpjs_ketenagakerjaan'),
            'bpjs_tk_no' => $request->input('bpjs_tk_no'),
            'bank_name' => $request->input('bank_name'),
            'bank_account_number' => $request->input('bank_account_number'),
            'bank_account_holder' => $request->input('bank_account_holder'),
        ];

        if ($request->has('base_salary')) {
            $updateData['base_salary'] = (float) $request->input('base_salary', 0);
        }
        if ($request->has('incentive_rate')) {
            $updateData['incentive_rate'] = (float) $request->input('incentive_rate', 0);
        }

        $user->update($updateData);

        // Sinkronkan ke data Employee HR jika ada
        $emp = $user->employee ?: \App\Models\Employee::where('email', $user->email)
            ->orWhere('name', $user->name)
            ->orWhere(function($q) use ($user) {
                if (!empty($user->phone)) {
                    $q->where('phone', $user->phone);
                }
            })
            ->first();
        if ($emp) {
            $empUpdate = [];
            if ($request->has('base_salary')) {
                $empUpdate['base_salary'] = (float) $request->input('base_salary', 0);
            }
            if ($request->filled('bank_name')) $empUpdate['bank_name'] = $request->input('bank_name');
            if ($request->filled('bank_account_number')) $empUpdate['bank_account_number'] = $request->input('bank_account_number');
            if ($request->filled('bank_account_holder')) $empUpdate['bank_account_holder'] = $request->input('bank_account_holder');
            if (!empty($empUpdate)) {
                $emp->update($empUpdate);
            }
        }

        return back()->with('success', "Data Gaji Pokok, BPJS & Rekening untuk {$user->name} berhasil diperbarui.");
    }

    /**
     * Tambah Insentif Manual
     */
    public function storeIncentive(Request $request, User $user)
    {
        $authUser = auth()->user();
        if (!$authUser->is_admin && !$authUser->hasRole('admin') && !$authUser->can('payroll_incentives')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah insentif.');
        }

        $date = $request->input('incentive_date', $request->input('date', now()->toDateString()));
        $request->merge(['incentive_date' => $date]);

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'incentive_date' => 'required|date'
        ]);

        Incentive::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'type' => 'manual',
            'description' => $request->description,
            'incentive_date' => $request->incentive_date,
            'status' => 'pending'
        ]);

        return back()->with('success', 'Insentif manual berhasil ditambahkan untuk ' . $user->name);
    }
    
    /**
     * Hapus Insentif Manual
     */
    public function destroyIncentive(Incentive $incentive)
    {
        $authUser = auth()->user();
        if (!$authUser->is_admin && !$authUser->hasRole('admin') && !$authUser->can('payroll_incentives') && !$authUser->can('payroll_incentives_delete') && !$authUser->can('payroll_manage')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus insentif.');
        }

        if ($incentive->status === 'paid') {
            return back()->with('error', 'Tidak bisa menghapus insentif yang sudah dicairkan.');
        }
        $id = $incentive->id;
        $incentive->delete();
        \App\Models\TargetMarketLog::where('incentive_id', $id)->update([
            'status' => 'approved',
            'incentive_id' => null,
        ]);
        return back()->with('success', 'Insentif berhasil dihapus.');
    }

    /**
     * Tambah Potongan Manual
     */
    public function storeDeduction(Request $request, User $user)
    {
        $authUser = auth()->user();
        if (!$authUser->is_admin && !$authUser->hasRole('admin') && !$authUser->can('payroll_incentives') && !$authUser->can('payroll_deductions') && !$authUser->can('payroll_manage')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah potongan.');
        }

        $date = $request->input('deduction_date', $request->input('date', now()->toDateString()));
        $request->merge(['deduction_date' => $date]);

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'deduction_date' => 'required|date'
        ]);

        \App\Models\Deduction::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'type' => 'manual',
            'description' => $request->description,
            'deduction_date' => $request->deduction_date,
            'status' => 'pending'
        ]);

        return back()->with('success', 'Potongan berhasil ditambahkan untuk ' . $user->name);
    }
    
    /**
     * Hapus Potongan Manual
     */
    public function destroyDeduction(\App\Models\Deduction $deduction)
    {
        $authUser = auth()->user();
        if (!$authUser->is_admin && !$authUser->hasRole('admin') && !$authUser->can('payroll_incentives') && !$authUser->can('payroll_incentives_delete') && !$authUser->can('payroll_deductions') && !$authUser->can('payroll_manage')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus potongan.');
        }

        if ($deduction->status === 'applied') {
            return back()->with('error', 'Tidak bisa menghapus potongan yang sudah dicairkan.');
        }
        $deduction->delete();
        return back()->with('success', 'Potongan berhasil dihapus.');
    }

    /**
     * Cairkan Gaji Tunggal Karyawan (Disbursement)
     */
    public function disburse(Request $request, User $user)
    {
        $authUser = auth()->user();
        if (!$authUser->is_admin && !$authUser->hasRole('admin') && !$authUser->can('payroll_disburse')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencairkan gaji.');
        }

        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        // Pastikan belum dicairkan
        $existing = Payroll::where('user_id', $user->id)->where('month', $month)->where('year', $year)->first();
        if ($existing && $existing->status === 'paid') {
            return back()->with('error', 'Gaji untuk periode ini sudah dicairkan sebelumnya.');
        }

        DB::beginTransaction();
        try {
            // Get all pending incentives for this month
            $pendingIncentives = Incentive::where('user_id', $user->id)
                ->whereMonth('incentive_date', $month)
                ->whereYear('incentive_date', $year)
                ->where('status', 'pending')
                ->get();

            // Get all pending deductions for this month
            $pendingDeductions = \App\Models\Deduction::where('user_id', $user->id)
                ->whereMonth('deduction_date', $month)
                ->whereYear('deduction_date', $year)
                ->where('status', 'pending')
                ->get();

            $totalIncentive = (float) $pendingIncentives->sum('amount');
            $totalDeduction = (float) $pendingDeductions->sum('amount');

            // Hitung Gaji Pokok & BPJS
            $rawBaseSalary = (float) $user->base_salary;
            $emp = $user->employee ?: \App\Models\Employee::where('email', $user->email)
                ->orWhere('name', $user->name)
                ->orWhere(function($q) use ($user) {
                    if (!empty($user->phone)) {
                        $q->where('phone', $user->phone);
                    }
                })
                ->first();
            if ($rawBaseSalary <= 0) {
                if ($emp && (float) $emp->base_salary > 0) {
                    $rawBaseSalary = (float) $emp->base_salary;
                    $user->update(['base_salary' => $rawBaseSalary]);
                }
            } elseif ($emp && (float) $emp->base_salary <= 0) {
                $emp->update(['base_salary' => $rawBaseSalary]);
            }
            $bpjs = self::calculateBpjs($user, $rawBaseSalary);
            $netSalary = max(0, $rawBaseSalary + $totalIncentive - $totalDeduction - $bpjs['total_employee']);

            $bpjsNotes = [];
            if ($bpjs['total_employee'] > 0) {
                $bpjsNotes[] = "Potong BPJS: Rp " . number_format($bpjs['total_employee'], 0, ',', '.');
            }
            if ($bpjs['total_company'] > 0) {
                $bpjsNotes[] = "Tunjangan BPJS Perusahaan: Rp " . number_format($bpjs['total_company'], 0, ',', '.');
            }
            $bpjsNotesStr = !empty($bpjsNotes) ? " [" . implode(' | ', $bpjsNotes) . "]" : "";

            // 1. Create Expense (Buku Kas Umum)
            $expense = Expense::create([
                'amount' => $netSalary,
                'description' => "Pencairan Gaji & Insentif - {$user->name} (Periode: " . str_pad($month, 2, '0', STR_PAD_LEFT) . "/{$year}){$bpjsNotesStr}",
                'expense_date' => now(),
                'user_id' => auth()->id()
            ]);

            // 2. Create/Update Payroll
            $payroll = Payroll::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'month' => $month,
                    'year' => $year
                ],
                [
                    'base_salary' => $rawBaseSalary,
                    'total_incentive' => $totalIncentive,
                    'total_deduction' => $totalDeduction,
                    'bpjs_kesehatan_employee' => $bpjs['kesehatan_employee'],
                    'bpjs_kesehatan_company' => $bpjs['kesehatan_company'],
                    'bpjs_tk_jht_employee' => $bpjs['tk_jht_employee'],
                    'bpjs_tk_jht_company' => $bpjs['tk_jht_company'],
                    'bpjs_tk_jp_employee' => $bpjs['tk_jp_employee'],
                    'bpjs_tk_jp_company' => $bpjs['tk_jp_company'],
                    'bpjs_tk_jkk_company' => $bpjs['tk_jkk_company'],
                    'bpjs_tk_jkm_company' => $bpjs['tk_jkm_company'],
                    'total_bpjs_employee' => $bpjs['total_employee'],
                    'total_bpjs_company' => $bpjs['total_company'],
                    'net_salary' => $netSalary,
                    'status' => 'paid',
                    'payment_date' => now(),
                    'expense_id' => $expense->id,
                    'notes' => 'Cair otomatis via sistem' . $bpjsNotesStr,
                    'disbursement_channel' => 'manual',
                ]
            );

            // 3. Mark Incentives as Paid and link to Payroll
            foreach ($pendingIncentives as $inc) {
                $inc->update([
                    'status' => 'paid',
                    'payroll_id' => $payroll->id
                ]);
            }

            // 4. Mark Deductions as Applied and link to Payroll
            foreach ($pendingDeductions as $ded) {
                $ded->update([
                    'status' => 'applied',
                    'payroll_id' => $payroll->id
                ]);
            }

            // 5. Cek opsi Auto Transfer via Flip atau Xendit
            $flipService = new \App\Services\FlipDisbursementService();
            $xenditService = new \App\Services\XenditDisbursementService();

            $activeProvider = Setting::get('active_disbursement_provider', 'none');
            $requestedProvider = $request->input('provider');
            if ($request->boolean('use_flip')) $requestedProvider = 'flip';
            if ($request->boolean('use_xendit')) $requestedProvider = 'xendit';

            $provider = $requestedProvider ?: $activeProvider;
            $autoDisburseMsg = '';

            if ($provider === 'flip' && $netSalary > 0) {
                $shouldDisburseFlip = $request->boolean('use_flip') || ($flipService->isEnabled() && Setting::get('flip_auto_disburse_on_approve') === '1') || $request->filled('provider');
                if ($shouldDisburseFlip && $flipService->isEnabled()) {
                    $flipResult = $flipService->disbursePayroll(
                        $payroll,
                        $user,
                        $netSalary,
                        "Gaji {$user->name} Periode " . str_pad($month, 2, '0', STR_PAD_LEFT) . "/{$year}"
                    );

                    if ($flipResult['status'] === 'error') {
                        DB::rollBack();
                        return back()->with('error', "Gagal Auto Transfer Flip: " . $flipResult['message']);
                    }

                    $autoDisburseMsg = " | Auto Transfer Flip Berhasil ({$flipResult['disbursement_status']}) Ref ID: {$flipResult['disbursement_id']}";
                }
            } elseif ($provider === 'xendit' && $netSalary > 0) {
                $shouldDisburseXendit = $request->boolean('use_xendit') || ($xenditService->isEnabled() && Setting::get('xendit_auto_disburse_on_approve') === '1') || $request->filled('provider');
                if ($shouldDisburseXendit && $xenditService->isEnabled()) {
                    $xenditResult = $xenditService->disbursePayroll(
                        $payroll,
                        $user,
                        $netSalary,
                        "Gaji {$user->name} Periode " . str_pad($month, 2, '0', STR_PAD_LEFT) . "/{$year}"
                    );

                    if ($xenditResult['status'] === 'error') {
                        DB::rollBack();
                        return back()->with('error', "Gagal Auto Transfer Xendit: " . $xenditResult['message']);
                    }

                    $autoDisburseMsg = " | Auto Transfer Xendit Berhasil ({$xenditResult['disbursement_status']}) Ref: {$xenditResult['disbursement_id']}";
                }
            }

            DB::commit();
            return back()->with('success', "Gaji {$user->name} berhasil dicairkan (Net: Rp " . number_format($netSalary, 0, ',', '.') . "){$autoDisburseMsg}. Pengeluaran otomatis dicatat di Buku Kas.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mencairkan gaji: ' . $e->getMessage());
        }
    }

    /**
     * Cairkan Gaji Seluruh Karyawan Sekaligus (Batch Disbursement)
     */
    public function disburseAll(Request $request)
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->is_admin || $authUser->hasRole('admin'));
        if (!$isSuperAdmin && !$authUser->can('payroll_disburse')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mencairkan gaji massal.');
        }

        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        $excludedRoles = ['customer', 'pelanggan', 'reseller', 'superadmin', 'super admin'];

        $usersQuery = User::where('is_active', true)
            ->whereNotIn('role', $excludedRoles)
            ->whereDoesntHave('roles', function($q) use ($excludedRoles) {
                $q->whereIn('name', $excludedRoles);
            })
            ->whereDoesntHave('customer')
            ->whereDoesntHave('reseller')
            ->where(function($q) {
                $q->where('role', '!=', 'admin')
                  ->orWhere(function($sub) {
                      $sub->where('name', 'not like', '%super admin%')
                          ->where('name', 'not like', '%superadmin%')
                          ->where('email', 'not like', '%superadmin%');
                  });
            });
        if (!$isSuperAdmin && !$authUser->can('payroll_view_all')) {
            if ($authUser->can('payroll_view_area') && $authUser->area_id) {
                $usersQuery->where('area_id', $authUser->area_id);
            }
        }
        $users = $usersQuery->get();
        $disbursedCount = 0;
        $totalNetDisbursed = 0;

        $flipService = new \App\Services\FlipDisbursementService();
        $xenditService = new \App\Services\XenditDisbursementService();

        $activeProvider = Setting::get('active_disbursement_provider', 'none');
        $requestedProvider = $request->input('provider');
        if ($request->boolean('use_flip')) $requestedProvider = 'flip';
        if ($request->boolean('use_xendit')) $requestedProvider = 'xendit';

        $provider = $requestedProvider ?: $activeProvider;

        $shouldUseFlip = ($provider === 'flip') && ($request->boolean('use_flip') || ($flipService->isEnabled() && Setting::get('flip_auto_disburse_on_approve') === '1') || $request->filled('provider'));
        $shouldUseXendit = ($provider === 'xendit') && ($request->boolean('use_xendit') || ($xenditService->isEnabled() && Setting::get('xendit_auto_disburse_on_approve') === '1') || $request->filled('provider'));

        $transferredCount = 0;
        $transferErrors = [];

        DB::beginTransaction();
        try {
            foreach ($users as $user) {
                $existing = Payroll::where('user_id', $user->id)->where('month', $month)->where('year', $year)->first();
                if ($existing && $existing->status === 'paid') {
                    continue;
                }

                $pendingIncentives = Incentive::where('user_id', $user->id)
                    ->whereMonth('incentive_date', $month)
                    ->whereYear('incentive_date', $year)
                    ->where('status', 'pending')
                    ->get();

                $pendingDeductions = \App\Models\Deduction::where('user_id', $user->id)
                    ->whereMonth('deduction_date', $month)
                    ->whereYear('deduction_date', $year)
                    ->where('status', 'pending')
                    ->get();

                $totalIncentive = (float) $pendingIncentives->sum('amount');
                $totalDeduction = (float) $pendingDeductions->sum('amount');
                $rawBaseSalary = (float) $user->base_salary;
                $emp = $user->employee ?: \App\Models\Employee::where('email', $user->email)
                    ->orWhere('name', $user->name)
                    ->orWhere(function($q) use ($user) {
                        if (!empty($user->phone)) {
                            $q->where('phone', $user->phone);
                        }
                    })
                    ->first();
                if ($rawBaseSalary <= 0) {
                    if ($emp && (float) $emp->base_salary > 0) {
                        $rawBaseSalary = (float) $emp->base_salary;
                        $user->update(['base_salary' => $rawBaseSalary]);
                    }
                } elseif ($emp && (float) $emp->base_salary <= 0) {
                    $emp->update(['base_salary' => $rawBaseSalary]);
                }
                $bpjs = self::calculateBpjs($user, $rawBaseSalary);
                $netSalary = max(0, $rawBaseSalary + $totalIncentive - $totalDeduction - $bpjs['total_employee']);

                $bpjsNotes = [];
                if ($bpjs['total_employee'] > 0) {
                    $bpjsNotes[] = "Potong BPJS: Rp " . number_format($bpjs['total_employee'], 0, ',', '.');
                }
                if ($bpjs['total_company'] > 0) {
                    $bpjsNotes[] = "Tunjangan BPJS: Rp " . number_format($bpjs['total_company'], 0, ',', '.');
                }
                $bpjsNotesStr = !empty($bpjsNotes) ? " [" . implode(' | ', $bpjsNotes) . "]" : "";

                $channel = 'manual';
                if ($shouldUseFlip) $channel = 'flip';
                elseif ($shouldUseXendit) $channel = 'xendit';

                $expense = Expense::create([
                    'amount' => $netSalary,
                    'description' => "Pencairan Gaji Batch - {$user->name} (Periode: " . str_pad($month, 2, '0', STR_PAD_LEFT) . "/{$year}){$bpjsNotesStr}",
                    'expense_date' => now(),
                    'user_id' => auth()->id()
                ]);

                $payroll = Payroll::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'month' => $month,
                        'year' => $year
                    ],
                    [
                        'base_salary' => $rawBaseSalary,
                        'total_incentive' => $totalIncentive,
                        'total_deduction' => $totalDeduction,
                        'bpjs_kesehatan_employee' => $bpjs['kesehatan_employee'],
                        'bpjs_kesehatan_company' => $bpjs['kesehatan_company'],
                        'bpjs_tk_jht_employee' => $bpjs['tk_jht_employee'],
                        'bpjs_tk_jht_company' => $bpjs['tk_jht_company'],
                        'bpjs_tk_jp_employee' => $bpjs['tk_jp_employee'],
                        'bpjs_tk_jp_company' => $bpjs['tk_jp_company'],
                        'bpjs_tk_jkk_company' => $bpjs['tk_jkk_company'],
                        'bpjs_tk_jkm_company' => $bpjs['tk_jkm_company'],
                        'total_bpjs_employee' => $bpjs['total_employee'],
                        'total_bpjs_company' => $bpjs['total_company'],
                        'net_salary' => $netSalary,
                        'status' => 'paid',
                        'payment_date' => now(),
                        'expense_id' => $expense->id,
                        'notes' => 'Batch Auto Disburse via sistem' . $bpjsNotesStr,
                        'disbursement_channel' => $channel,
                    ]
                );

                foreach ($pendingIncentives as $inc) {
                    $inc->update(['status' => 'paid', 'payroll_id' => $payroll->id]);
                }
                foreach ($pendingDeductions as $ded) {
                    $ded->update(['status' => 'applied', 'payroll_id' => $payroll->id]);
                }

                // Eksekusi transfer otomatis jika aktif
                if ($shouldUseFlip && $netSalary > 0) {
                    $flipResult = $flipService->disbursePayroll(
                        $payroll,
                        $user,
                        $netSalary,
                        "Gaji Batch {$user->name} " . str_pad($month, 2, '0', STR_PAD_LEFT) . "/{$year}"
                    );

                    if ($flipResult['status'] === 'error') {
                        $transferErrors[] = "{$user->name}: " . $flipResult['message'];
                    } else {
                        $transferredCount++;
                    }
                } elseif ($shouldUseXendit && $netSalary > 0) {
                    $xenditResult = $xenditService->disbursePayroll(
                        $payroll,
                        $user,
                        $netSalary,
                        "Gaji Batch {$user->name} " . str_pad($month, 2, '0', STR_PAD_LEFT) . "/{$year}"
                    );

                    if ($xenditResult['status'] === 'error') {
                        $transferErrors[] = "{$user->name}: " . $xenditResult['message'];
                    } else {
                        $transferredCount++;
                    }
                }

                $disbursedCount++;
                $totalNetDisbursed += $netSalary;
            }

            DB::commit();

            $extraMsg = "";
            if ($shouldUseFlip || $shouldUseXendit) {
                $providerName = $shouldUseFlip ? 'Flip' : 'Xendit';
                $extraMsg = ". Auto Transfer {$providerName}: {$transferredCount} staf berhasil dikirim.";
                if (!empty($transferErrors)) {
                    $extraMsg .= " Beberapa gagal: " . implode('; ', array_slice($transferErrors, 0, 2));
                }
            }

            return back()->with('success', "Berhasil mencairkan gaji massal untuk {$disbursedCount} staf (Total Net: Rp " . number_format($totalNetDisbursed, 0, ',', '.') . "){$extraMsg}. Pengeluaran otomatis dicatat di Buku Kas.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mencairkan gaji massal: ' . $e->getMessage());
        }
    }

    /**
     * Webhook Callback dari Xendit untuk status disbursement
     */
    public function handleXenditWebhook(Request $request)
    {
        $xenditService = new \App\Services\XenditDisbursementService();
        $expectedToken = $xenditService->getWebhookToken();
        if ($expectedToken) {
            $receivedToken = $request->header('x-callback-token');
            if ($receivedToken !== $expectedToken) {
                return response()->json(['error' => 'Invalid callback token'], 403);
            }
        }

        $id = $request->input('id');
        $externalId = $request->input('external_id');
        $status = strtoupper($request->input('status', ''));

        if ($id || $externalId) {
            $payroll = Payroll::where('disbursement_id', $id)
                ->orWhere('disbursement_reference', $externalId)
                ->first();

            if ($payroll) {
                $payroll->update([
                    'disbursement_status' => $status,
                    'disbursement_error' => $request->input('failure_code') ? ('Failure: ' . $request->input('failure_code')) : null,
                ]);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Webhook Callback dari Flip for Business untuk status disbursement
     */
    public function handleFlipWebhook(Request $request)
    {
        $flipService = new \App\Services\FlipDisbursementService();
        $expectedToken = $flipService->getValidationToken();

        if ($expectedToken) {
            $receivedToken = $request->header('x-callback-token') 
                ?: $request->input('token') 
                ?: $request->header('token');

            if ($receivedToken && $receivedToken !== $expectedToken) {
                return response()->json(['error' => 'Invalid validation token'], 403);
            }
        }

        // Flip data payload can come as JSON or form string 'data'
        $payload = $request->input('data');
        if (is_string($payload)) {
            $data = json_decode($payload, true) ?: [];
        } else {
            $data = $request->all();
        }

        $id = $data['id'] ?? null;
        $rawStatus = strtoupper($data['status'] ?? '');
        $mappedStatus = ($rawStatus === 'DONE') ? 'COMPLETED' : $rawStatus;

        if ($id) {
            $payroll = Payroll::where('disbursement_id', (string) $id)
                ->first();

            if ($payroll) {
                $payroll->update([
                    'disbursement_status' => $mappedStatus,
                    'disbursement_error' => in_array($mappedStatus, ['FAILED', 'CANCELLED']) ? ($data['error'] ?? 'Transfer dibatalkan / gagal di Flip') : null,
                    'disbursement_payload' => json_encode($data),
                ]);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Bersihkan insentif otomatis yang pelanggannya sudah tidak ada di data pelanggan
     */
    public function cleanOrphanedIncentives(Request $request)
    {
        $authUser = auth()->user();
        $isSuperAdmin = $authUser && ($authUser->is_admin || $authUser->hasRole('admin'));
        if (!$isSuperAdmin && !$authUser->can('payroll_manage') && !$authUser->can('payroll_incentives') && !$authUser->can('payroll_incentives_delete')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membersihkan data insentif.');
        }

        $custCodes = \App\Models\Customer::pluck('customer_code')->filter()->toArray();
        $custIds = \App\Models\Customer::pluck('id')->toArray();
        $custNames = \App\Models\Customer::pluck('name')->map(fn($n) => strtolower(trim($n)))->toArray();

        $pendingAuto = Incentive::where('status', 'pending')
            ->where('type', '!=', 'manual')
            ->get();

        $toDeleteIds = [];
        $totalNominal = 0;

        foreach ($pendingAuto as $inc) {
            $matched = false;

            if (!empty($inc->reference_id)) {
                if (preg_match('/cust_(\d+)/i', $inc->reference_id, $m)) {
                    if (in_array((int)$m[1], $custIds)) {
                        $matched = true;
                    }
                }
                if (!$matched) {
                    foreach ($custCodes as $code) {
                        if (str_contains($inc->reference_id, $code)) {
                            $matched = true;
                            break;
                        }
                    }
                }
            }

            if (!$matched && preg_match('/\((CUS-[^)]+)\)/i', $inc->description, $m)) {
                if (in_array(strtoupper($m[1]), array_map('strtoupper', $custCodes))) {
                    $matched = true;
                }
            }

            if (!$matched) {
                $cleanName = trim(preg_replace('/^(Fee (Booking|Survey|Pasang|Aktivasi) Pelanggan:?\s*)/i', '', $inc->description));
                $cleanName = strtolower(trim(preg_replace('/\s*\(CUS-[^)]+\)/', '', $cleanName)));
                if (!empty($cleanName) && in_array($cleanName, $custNames)) {
                    $matched = true;
                }
            }

            if (!$matched) {
                $toDeleteIds[] = $inc->id;
                $totalNominal += (float) $inc->amount;
            }
        }

        // 2. Pemindaian Potongan Otomatis (Pinalti/Clawback) tanpa Pelanggan
        $pendingDeductions = \App\Models\Deduction::where('status', 'pending')
            ->where('type', '!=', 'manual')
            ->get();

        $toDeleteDedIds = [];
        $totalNominalDed = 0;

        foreach ($pendingDeductions as $ded) {
            $matched = false;

            if (!empty($ded->reference_id)) {
                if (preg_match('/cust_(\d+)/i', $ded->reference_id, $m)) {
                    if (in_array((int)$m[1], $custIds)) {
                        $matched = true;
                    }
                }
                if (!$matched) {
                    foreach ($custCodes as $code) {
                        if (str_contains($ded->reference_id, $code)) {
                            $matched = true;
                            break;
                        }
                    }
                }
            }

            if (!$matched && preg_match('/\((CUS-[^)]+)\)/i', $ded->description, $m)) {
                if (in_array(strtoupper($m[1]), array_map('strtoupper', $custCodes))) {
                    $matched = true;
                }
            }

            if (!$matched) {
                $cleanName = trim(preg_replace('/^(Pinalti Stop Dini[^:]*:\s*(Pemotongan Fee (Booking|Survey|Pasang|Aktivasi)\s*)?)/i', '', $ded->description));
                $cleanName = preg_replace('/\s*\(CUS-[^)]+\).*/i', '', $cleanName);
                $cleanName = strtolower(trim($cleanName));
                if (!empty($cleanName) && in_array($cleanName, $custNames)) {
                    $matched = true;
                }
            }

            if (!$matched) {
                $toDeleteDedIds[] = $ded->id;
                $totalNominalDed += (float) $ded->amount;
            }
        }

        $count = count($toDeleteIds);
        $countDed = count($toDeleteDedIds);

        if ($count > 0 || $countDed > 0) {
            foreach (array_chunk($toDeleteIds, 500) as $chunk) {
                Incentive::whereIn('id', $chunk)->delete();
            }
            foreach (array_chunk($toDeleteDedIds, 500) as $chunk) {
                \App\Models\Deduction::whereIn('id', $chunk)->delete();
            }
            \Illuminate\Support\Facades\Log::info("[PayrollController@cleanOrphanedIncentives] Berhasil menghapus {$count} insentif dan {$countDed} potongan tanpa pelanggan oleh {$authUser->name}");
            return redirect()->back()->with('success', "Berhasil menghapus {$count} insentif dan {$countDed} potongan pinalti tanpa data pelanggan.");
        }

        return redirect()->back()->with('info', 'Tidak ditemukan data insentif atau potongan tanpa pelanggan. Semua data valid.');
    }
}
