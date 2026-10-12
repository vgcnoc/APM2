<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\VoucherProfile;
use App\Models\Voucher;
use App\Models\Router;
use App\Models\ResellerBalanceRequest;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ResellerClientController extends Controller
{
    /**
     * Display the reseller dashboard.
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        
        // Find reseller by matching user_id
        $reseller = \App\Models\Reseller::where('user_id', $user->id)->first();
        if (!$reseller) {
            if ($user->customer) {
                return redirect()->route('customer-area.dashboard');
            }
            abort(403, 'Anda tidak memiliki akses sebagai reseller.');
        }

        $vouchers = Voucher::where('reseller_id', $reseller->id)
            ->latest()
            ->paginate(10);

        $profiles = $user->voucherProfiles()->where('is_customer_free', false)->get();
        if ($profiles->isEmpty()) {
            $profiles = VoucherProfile::where('is_customer_free', false)->get();
        }

        // Hitung Total Pemasukan hanya dari voucher yang SUDAH TERJUAL / DIGUNAKAN / EXPIRED
        $income = Voucher::where('reseller_id', $reseller->id)
            ->where('status', '!=', 'available')
            ->join('voucher_profiles', 'vouchers.voucher_profile_id', '=', 'voucher_profiles.id')
            ->sum('voucher_profiles.price');

        $totalMargin = Voucher::where('reseller_id', $reseller->id)
            ->where('status', '!=', 'available')
            ->join('voucher_profiles', 'vouchers.voucher_profile_id', '=', 'voucher_profiles.id')
            ->sum('voucher_profiles.fee_reseller');

        // Hitung jumlah voucher yang masih di stok (tersedia)
        $availableCount = Voucher::where('reseller_id', $reseller->id)
            ->where('status', 'available')
            ->count();

        // Hitung Tagihan Kasbon Saldo Belum Lunas
        $customer = $reseller->customer;
        $totalUnpaidKasbon = 0;
        $unpaidKasbonInvoices = [];

        if ($customer) {
            $kasbonInvoices = Invoice::with('payments')
                ->where('customer_id', $customer->id)
                ->where('is_reseller_balance', true)
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->latest('due_date')
                ->get();

            $totalUnpaidKasbon = (float) $kasbonInvoices->sum(function ($inv) {
                $verifiedPaid = $inv->payments->where('status', 'verified')->sum('amount');
                return max(0, $inv->amount - $verifiedPaid);
            });

            $unpaidKasbonInvoices = $kasbonInvoices->map(function ($inv) {
                $verifiedPaid = $inv->payments->where('status', 'verified')->sum('amount');
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'issued_date' => $inv->issued_date ? Carbon::parse($inv->issued_date)->translatedFormat('d M Y') : '-',
                    'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->translatedFormat('d M Y') : '-',
                    'amount' => (float) $inv->amount,
                    'remaining' => max(0, (float) ($inv->amount - $verifiedPaid)),
                    'status' => $inv->status,
                    'notes' => $inv->notes,
                ];
            })->values()->all();
        }

        return Inertia::render('ClientArea/Dashboard', [
            'reseller' => $reseller->load('customer'),
            'vouchers' => $vouchers,
            'profiles' => $profiles,
            'income' => $income,
            'total_margin' => $totalMargin,
            'available_count' => $availableCount,
            'total_unpaid_kasbon' => $totalUnpaidKasbon,
            'unpaid_kasbon_invoices' => $unpaidKasbonInvoices,
        ]);
    }

    /**
     * Tampilkan halaman Tagihan & Kasbon untuk Reseller.
     */
    public function billing(Request $request)
    {
        $user = auth()->user();
        $reseller = \App\Models\Reseller::where('user_id', $user->id)->first();
        if (!$reseller) {
            if ($user->customer) {
                return redirect()->route('customer-area.billing');
            }
            abort(403, 'Akses ditolak.');
        }

        $customer = $reseller->customer;
        $invoicesQuery = Invoice::with('payments')
            ->where('is_reseller_balance', true);

        if ($customer) {
            $invoicesQuery->where('customer_id', $customer->id);
        } else {
            $invoicesQuery->whereRaw('1 = 0');
        }

        if ($request->filled('type')) {
            if ($request->type === 'kasbon') {
                $invoicesQuery->where(function ($q) {
                    $q->where('notes', 'like', '%kasbon%')
                      ->orWhere('notes', 'not like', '%top up%');
                });
            } elseif ($request->type === 'topup') {
                $invoicesQuery->where('notes', 'like', '%top up%');
            }
        }

        if ($request->filled('status')) {
            if ($request->status === 'unpaid') {
                $invoicesQuery->whereIn('status', ['unpaid', 'partial', 'overdue']);
            } elseif ($request->status === 'paid') {
                $invoicesQuery->where('status', 'paid');
            }
        }

        $invoices = $invoicesQuery->latest('due_date')->paginate(10)->withQueryString();

        // Hitung total kasbon belum lunas
        $totalUnpaidKasbon = 0;
        if ($customer) {
            $kasbonInvoices = Invoice::with('payments')
                ->where('customer_id', $customer->id)
                ->where('is_reseller_balance', true)
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->get();

            $totalUnpaidKasbon = (float) $kasbonInvoices->sum(function ($inv) {
                $verifiedPaid = $inv->payments->where('status', 'verified')->sum('amount');
                return max(0, $inv->amount - $verifiedPaid);
            });
        }

        return Inertia::render('ClientArea/Billing', [
            'reseller' => $reseller->load('customer'),
            'invoices' => $invoices,
            'total_unpaid_kasbon' => $totalUnpaidKasbon,
            'filters' => $request->only(['type', 'status']),
        ]);
    }

    /**
     * Generate vouchers for the reseller.
     */
    public function generateVouchers(Request $request)
    {
        $user = auth()->user();
        $reseller = \App\Models\Reseller::where('user_id', $user->id)->first();
        
        if (!$reseller) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'profile_id' => 'required|exists:voucher_profiles,id',
            'qty' => 'required|integer|min:1|max:100',
            'combination' => 'nullable|string|in:alphanumeric,numeric,alpha',
        ]);

        $profile = VoucherProfile::where('is_customer_free', false)->findOrFail($validated['profile_id']);
        
        // Logika Pemotongan Saldo Reseller (Harga Modal):
        // Harga Modal Reseller = Harga Jual - Fee/Margin Reseller
        // Contoh: Harga Jual Rp 3.000, Fee Rp 1.000 -> Modal Rp 2.000
        $sellPrice = (float) $profile->price;
        $margin = (float) ($profile->fee_reseller ?? 0);
        $costPerVoucher = max(0, $sellPrice - $margin);
        $totalCost = $costPerVoucher * $validated['qty'];
        $totalProfit = $margin * $validated['qty'];
        
        if ($reseller->balance < $totalCost) {
            return redirect()->back()->withErrors([
                'error' => 'Saldo Anda tidak mencukupi untuk generate ' . $validated['qty'] . ' voucher. Butuh saldo Rp ' . number_format($totalCost, 0, ',', '.') . ' (Modal Rp ' . number_format($costPerVoucher, 0, ',', '.') . '/voucher). Saldo Anda saat ini: Rp ' . number_format($reseller->balance, 0, ',', '.')
            ]);
        }

        // Deduct balance
        $reseller->balance -= $totalCost;
        $reseller->save();

        // Record debit transaction
        \App\Models\ResellerTransaction::create([
            'reseller_id' => $reseller->id,
            'type' => 'debit',
            'amount' => $totalCost,
            'description' => "Pembelian {$validated['qty']} Voucher {$profile->name} (Modal: Rp " . number_format($costPerVoucher, 0, ',', '.') . "/voucher, Margin: Rp " . number_format($margin, 0, ',', '.') . "/voucher)",
            'reference_id' => 'VOUCHER-CLIENT-GEN',
        ]);

        $combination = $validated['combination'] ?? 'alphanumeric';
        
        $vouchers = [];
        $now = now();
        for ($i = 0; $i < $validated['qty']; $i++) {
            if ($combination === 'numeric') {
                $code = substr(str_shuffle(str_repeat('0123456789', 5)), 0, 6);
            } elseif ($combination === 'alpha') {
                $code = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 5)), 0, 6);
            } else {
                $code = strtoupper(Str::random(6));
            }

            while (Voucher::where('code', $code)->exists()) {
                $code = strtoupper(Str::random(6));
            }
            
            $vouchers[] = [
                'voucher_profile_id' => $profile->id,
                'reseller_id' => $reseller->id,
                'code' => $code,
                'username' => $code,
                'password' => $code,
                'status' => 'available',
                'is_free' => false,
                'payment_method' => 'balance',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Voucher::insert($vouchers);

        // Sync ke RADIUS
        $createdVouchers = Voucher::whereIn('code', array_column($vouchers, 'code'))->get();
        app(\App\Services\RadiusService::class)->syncVouchers($createdVouchers);

        return redirect()->back()->with('success', $validated['qty'] . ' Voucher berhasil digenerate. Saldo terpotong Rp ' . number_format($totalCost, 0, ',', '.') . ' (Modal Rp ' . number_format($costPerVoucher, 0, ',', '.') . '/voucher, Estimasi Keuntungan Rp ' . number_format($totalProfit, 0, ',', '.') . ').');
    }
}
