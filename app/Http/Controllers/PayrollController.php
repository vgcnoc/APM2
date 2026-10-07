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
     * Dashboard Penggajian & Insentif
     */
    public function index(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        
        // Pengaturan Tanggal Pencairan
        $disbursementDate = Setting::get('payroll_disbursement_date', '25'); // Default tanggal 25

        $users = User::where('is_active', true)
            ->with([
                'incentives' => function($q) use ($month, $year) {
                    $q->whereMonth('incentive_date', $month)->whereYear('incentive_date', $year);
                }, 
                'deductions' => function($q) use ($month, $year) {
                    $q->whereMonth('deduction_date', $month)->whereYear('deduction_date', $year);
                },
                'payrolls' => function($q) use ($month, $year) {
                    $q->where('month', $month)->where('year', $year);
                }
            ])
            ->get()
            ->map(function ($user) {
                $pendingIncentives = $user->incentives->where('status', 'pending');
                $totalAuto = $pendingIncentives->where('type', 'auto')->sum('amount');
                $totalManual = $pendingIncentives->where('type', 'manual')->sum('amount');
                
                $pendingDeductions = $user->deductions->where('status', 'pending');
                $totalDeduction = $pendingDeductions->sum('amount');
                
                $payroll = $user->payrolls->first();
                
                $isPaid = $payroll && $payroll->status === 'paid';
                
                // If paid, show historical data from payroll. If draft, show live calculation
                $baseSalary = $isPaid ? $payroll->base_salary : $user->base_salary;
                $totalIncentive = $isPaid ? $payroll->total_incentive : ($totalAuto + $totalManual);
                $finalDeduction = $isPaid ? $payroll->total_deduction : $totalDeduction;
                
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'base_salary' => $baseSalary,
                    'auto_incentive_rate' => $user->incentive_rate,
                    'total_auto_incentive' => $isPaid ? 0 : $totalAuto,
                    'total_manual_incentive' => $isPaid ? 0 : $totalManual,
                    'total_incentive' => $totalIncentive,
                    'total_deduction' => $finalDeduction,
                    'net_salary' => $isPaid ? $payroll->net_salary : max(0, $baseSalary + $totalIncentive - $finalDeduction),
                    'is_paid' => $isPaid,
                    'payment_date' => $isPaid ? $payroll->payment_date : null,
                    'incentives_list' => $user->incentives->toArray(),
                    'deductions_list' => $user->deductions->toArray()
                ];
            });

        $categories = \App\Models\PayrollCategory::where('is_active', true)->get();

        return Inertia::render('Payroll/Index', [
            'month' => (int)$month,
            'year' => (int)$year,
            'users' => $users,
            'settings' => [
                'disbursement_date' => $disbursementDate
            ],
            'payrollCategories' => $categories
        ]);
    }

    /**
     * Update Pengaturan Tanggal Pencairan
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'disbursement_date' => 'required|integer|min:1|max:31'
        ]);

        Setting::set('payroll_disbursement_date', $request->disbursement_date);

        return back()->with('success', 'Pengaturan pencairan gaji berhasil disimpan.');
    }

    /**
     * Tambah Insentif Manual
     */
    public function storeIncentive(Request $request, User $user)
    {
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
        if ($incentive->status === 'paid') {
            return back()->with('error', 'Tidak bisa menghapus insentif yang sudah dicairkan.');
        }
        $incentive->delete();
        return back()->with('success', 'Insentif berhasil dihapus.');
    }

    /**
     * Tambah Potongan Manual
     */
    public function storeDeduction(Request $request, User $user)
    {
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
        if ($deduction->status === 'applied') {
            return back()->with('error', 'Tidak bisa menghapus potongan yang sudah dicairkan.');
        }
        $deduction->delete();
        return back()->with('success', 'Potongan berhasil dihapus.');
    }

    /**
     * Cairkan Gaji (Disbursement)
     */
    public function disburse(Request $request, User $user)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

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

            $totalIncentive = $pendingIncentives->sum('amount');
            $totalDeduction = $pendingDeductions->sum('amount');
            $netSalary = max(0, $user->base_salary + $totalIncentive - $totalDeduction);

            // 1. Create Expense (Buku Kas Umum)
            $expense = Expense::create([
                'amount' => $netSalary,
                'description' => "Pencairan Gaji & Insentif - {$user->name} (Periode: " . str_pad($month, 2, '0', STR_PAD_LEFT) . "/{$year})",
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
                    'base_salary' => $user->base_salary,
                    'total_incentive' => $totalIncentive,
                    'total_deduction' => $totalDeduction,
                    'net_salary' => $netSalary,
                    'status' => 'paid',
                    'payment_date' => now(),
                    'expense_id' => $expense->id,
                    'notes' => 'Cair otomatis via sistem'
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

            DB::commit();
            return back()->with('success', 'Gaji dan insentif berhasil dicairkan. Pengeluaran otomatis tercatat di Buku Kas.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mencairkan gaji: ' . $e->getMessage());
        }
    }
}
