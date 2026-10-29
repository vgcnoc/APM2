<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\VoucherProfile;
use App\Models\Voucher;
use App\Models\Router;
use App\Models\ResellerBalanceRequest;
use App\Models\Invoice;
use App\Models\Reseller;
use App\Models\ResellerTransaction;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ResellerClientController extends Controller
{
    /**
     * Dapatkan data reseller atau inisialisasi otomatis untuk admin / role reseller.
     */
    private function getOrCreateReseller($user)
    {
        if (!$user) return null;

        $reseller = \App\Models\Reseller::where('user_id', $user->id)->first();
        if (!$reseller) {
            $userRole = strtolower(trim((string) $user->role));
            if (in_array($userRole, ['admin', 'superadmin', 'super admin', 'super-admin', 'reseller']) || (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->hasRole('reseller')))) {
                $reseller = \App\Models\Reseller::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'customer_id' => $user->customer?->id ?? null,
                        'balance' => in_array($userRole, ['admin', 'superadmin', 'super admin', 'super-admin']) ? 5000000 : 0,
                        'is_active' => true,
                    ]
                );
            }
        }
        if ($reseller && $reseller->isMaster() && empty($reseller->referral_code)) {
            $reseller->update(['referral_code' => Reseller::generateReferralCode()]);
        }
        return $reseller;
    }

    /**
     * Display the reseller dashboard.
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        
        // Find or create reseller
        $reseller = $this->getOrCreateReseller($user);
        if (!$reseller) {
            if ($user->customer) {
                return redirect()->route('customer-area.dashboard');
            }
            return redirect()->route('dashboard')->with('error', 'Akun Anda tidak memiliki hak akses sebagai reseller.');
        }

        $vouchers = Voucher::where('reseller_id', $reseller->id)
            ->with('profile')
            ->latest()
            ->paginate(10);

        $profiles = $user->voucherProfiles()->where('voucher_profiles.is_customer_free', false)->select('voucher_profiles.*')->get();
        if ($profiles->isEmpty()) {
            $profiles = VoucherProfile::where('is_customer_free', false)->get();
        }

        // Hitung Total Pemasukan hanya dari voucher yang SUDAH TERJUAL / DIGUNAKAN / EXPIRED
        $income = Voucher::where('vouchers.reseller_id', $reseller->id)
            ->where('vouchers.status', '!=', 'available')
            ->join('voucher_profiles', 'vouchers.voucher_profile_id', '=', 'voucher_profiles.id')
            ->sum('voucher_profiles.price');

        $totalMargin = Voucher::where('vouchers.reseller_id', $reseller->id)
            ->where('vouchers.status', '!=', 'available')
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
            'reseller' => $reseller->load(['customer', 'master.customer', 'master.user']),
            'is_master' => $reseller->isMaster(),
            'downlines' => $reseller->isMaster() ? $reseller->downlines()->with(['customer', 'user'])->get()->map(function ($d) {
                return [
                    'id' => $d->id,
                    'name' => $d->customer?->name ?: $d->user?->name,
                    'phone' => $d->customer?->phone ?: $d->user?->phone,
                    'address' => $d->customer?->address ?: '-',
                    'balance' => (float) $d->balance,
                    'created_at' => $d->created_at ? $d->created_at->translatedFormat('d M Y') : '-',
                ];
            })->values()->all() : [],
            'total_downlines_balance' => $reseller->isMaster() ? (float) $reseller->downlines()->sum('balance') : 0,
            'total_override_royalty' => $reseller->isMaster() ? (float) ResellerTransaction::where('reseller_id', $reseller->id)->where('reference_id', 'like', 'OVR-%')->sum('amount') : 0,
            'min_deposit' => $reseller->isMaster() ? (int) Setting::get('master_reseller_min_deposit', 2000000) : (int) Setting::get('reseller_min_deposit', 50000),
            'override_fee_amount' => (float) ($reseller->override_fee ?: Setting::get('master_reseller_override_fee', 500)),
            'all_resellers_for_transfer' => $reseller->isMaster() ? Reseller::with(['customer', 'user'])->where('id', '!=', $reseller->id)->where('is_active', true)->where('tier', '!=', 'master')->where(function ($q) use ($reseller) { $q->where('parent_id', $reseller->id)->orWhereNull('parent_id'); })->get()->map(function ($r) use ($reseller) {
                return [
                    'id' => $r->id,
                    'name' => $r->customer?->name ?: $r->user?->name,
                    'phone' => $r->customer?->phone ?: $r->user?->phone,
                    'balance' => (float) $r->balance,
                    'is_downline' => $r->parent_id === $reseller->id,
                ];
            })->values()->all() : [],
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
        $reseller = $this->getOrCreateReseller($user);
        if (!$reseller) {
            if ($user->customer) {
                return redirect()->route('customer-area.billing');
            }
            return redirect()->route('dashboard')->with('error', 'Akun Anda tidak memiliki hak akses sebagai reseller.');
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
            'reseller' => $reseller->load(['customer', 'master.customer', 'master.user']),
            'is_master' => $reseller->isMaster(),
            'downlines' => $reseller->isMaster() ? $reseller->downlines()->with(['customer', 'user'])->get()->map(function ($d) {
                return [
                    'id' => $d->id,
                    'name' => $d->customer?->name ?: $d->user?->name,
                    'phone' => $d->customer?->phone ?: $d->user?->phone,
                    'address' => $d->customer?->address ?: '-',
                    'balance' => (float) $d->balance,
                    'created_at' => $d->created_at ? $d->created_at->translatedFormat('d M Y') : '-',
                ];
            })->values()->all() : [],
            'total_downlines_balance' => $reseller->isMaster() ? (float) $reseller->downlines()->sum('balance') : 0,
            'total_override_royalty' => $reseller->isMaster() ? (float) ResellerTransaction::where('reseller_id', $reseller->id)->where('reference_id', 'like', 'OVR-%')->sum('amount') : 0,
            'min_deposit' => $reseller->isMaster() ? (int) Setting::get('master_reseller_min_deposit', 2000000) : (int) Setting::get('reseller_min_deposit', 50000),
            'override_fee_amount' => (float) ($reseller->override_fee ?: Setting::get('master_reseller_override_fee', 500)),
            'all_resellers_for_transfer' => $reseller->isMaster() ? Reseller::with(['customer', 'user'])->where('id', '!=', $reseller->id)->where('is_active', true)->where('tier', '!=', 'master')->where(function ($q) use ($reseller) { $q->where('parent_id', $reseller->id)->orWhereNull('parent_id'); })->get()->map(function ($r) use ($reseller) {
                return [
                    'id' => $r->id,
                    'name' => $r->customer?->name ?: $r->user?->name,
                    'phone' => $r->customer?->phone ?: $r->user?->phone,
                    'balance' => (float) $r->balance,
                    'is_downline' => $r->parent_id === $reseller->id,
                ];
            })->values()->all() : [],
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
        $reseller = $this->getOrCreateReseller($user);
        
        if (!$reseller) {
            return redirect()->back()->with('error', 'Akun Anda tidak memiliki hak akses sebagai reseller.');
        }

        $validated = $request->validate([
            'profile_id' => 'required|exists:voucher_profiles,id',
            'qty' => 'required|integer|min:1|max:100',
            'combination' => 'nullable|string|in:alphanumeric,alphanumeric_lower,alphanumeric_mixed,numeric,alpha,alpha_lower,clean_readable,clean_readable_lower,consonant_vowel,words_numeric',
        ]);

        $profile = VoucherProfile::where('is_customer_free', false)->findOrFail($validated['profile_id']);
        
        // Validasi hak akses profil voucher reseller
        if ($user->voucherProfiles()->exists() && !$user->voucherProfiles()->where('voucher_profiles.id', $profile->id)->exists()) {
            return redirect()->back()->withErrors([
                'error' => "Akun Anda tidak memiliki hak akses untuk menjual profil voucher '{$profile->name}'."
            ]);
        }
        
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
        
        $generateString = function($len, $comb) {
            switch ($comb) {
                case 'numeric':
                    $chars = '0123456789';
                    return substr(str_shuffle(str_repeat($chars, max(2, (int)ceil($len / 10) + 1))), 0, $len);
                case 'alpha':
                    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    return substr(str_shuffle(str_repeat($chars, max(2, (int)ceil($len / 26) + 1))), 0, $len);
                case 'alpha_lower':
                    $chars = 'abcdefghijklmnopqrstuvwxyz';
                    return substr(str_shuffle(str_repeat($chars, max(2, (int)ceil($len / 26) + 1))), 0, $len);
                case 'alphanumeric_lower':
                    $chars = '23456789abcdefghijklmnopqrstuvwxyz';
                    return substr(str_shuffle(str_repeat($chars, max(2, (int)ceil($len / 34) + 1))), 0, $len);
                case 'alphanumeric_mixed':
                    $chars = '23456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
                    return substr(str_shuffle(str_repeat($chars, max(2, (int)ceil($len / 56) + 1))), 0, $len);
                case 'clean_readable':
                    $chars = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
                    return substr(str_shuffle(str_repeat($chars, max(2, (int)ceil($len / 31) + 1))), 0, $len);
                case 'clean_readable_lower':
                    $chars = '23456789abcdefghjkmnpqrstuvwxyz';
                    return substr(str_shuffle(str_repeat($chars, max(2, (int)ceil($len / 31) + 1))), 0, $len);
                case 'words_numeric':
                    $words = ['KOPI', 'CERIA', 'MAJU', 'SUKSES', 'KILAT', 'SUPER', 'MANTAP', 'HEBAT', 'BINTANG', 'PINTAR', 'BERKAH', 'JAYA', 'CEPAT', 'ASIK', 'NET', 'HOT', 'SPEED'];
                    $word = $words[array_rand($words)];
                    $numLen = max(2, $len - strlen($word));
                    if ($numLen < 2) {
                        $word = substr($word, 0, max(2, $len - 2));
                        $numLen = $len - strlen($word);
                    }
                    $num = '';
                    for ($k = 0; $k < $numLen; $k++) {
                        $num .= mt_rand(0, 9);
                    }
                    return substr($word . $num, 0, $len);
                case 'consonant_vowel':
                    $consonants = 'BCDFGHJKLMNPQRSTVWXYZ';
                    $vowels = 'AEU';
                    $res = '';
                    $numDigits = max(2, (int)floor($len / 3));
                    $charLen = max(2, $len - $numDigits);
                    for ($k = 0; $k < $charLen; $k++) {
                        $pool = ($k % 2 === 0) ? $consonants : $vowels;
                        $res .= $pool[mt_rand(0, strlen($pool) - 1)];
                    }
                    for ($k = 0; $k < $numDigits; $k++) {
                        $res .= mt_rand(0, 9);
                    }
                    return substr($res, 0, $len);
                case 'alphanumeric':
                default:
                    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
                    return substr(str_shuffle(str_repeat($chars, max(2, (int)ceil($len / 32) + 1))), 0, $len);
            }
        };

        $vouchers = [];
        $now = now();
        for ($i = 0; $i < $validated['qty']; $i++) {
            $code = $generateString(6, $combination);
            if (!in_array($combination, ['alphanumeric_lower', 'alpha_lower', 'clean_readable_lower', 'alphanumeric_mixed'])) {
                $code = strtoupper($code);
            }

            while (Voucher::where('code', $code)->exists()) {
                $code = $generateString(6, $combination);
                if (!in_array($combination, ['alphanumeric_lower', 'alpha_lower', 'clean_readable_lower', 'alphanumeric_mixed'])) {
                    $code = strtoupper($code);
                }
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

    /**
     * Tampilkan halaman Loket Pembayaran (Biller) Reseller.
     */
    public function biller(Request $request)
    {
        $user = auth()->user();
        $reseller = $this->getOrCreateReseller($user);
        if (!$reseller) {
            return redirect()->route('dashboard')->with('error', 'Akun Anda tidak memiliki hak akses sebagai reseller.');
        }

        // Ambil riwayat pembayaran internet pelanggan yang diproses oleh reseller ini
        $recentPayments = \App\Models\Payment::with(['customer.package', 'customer.areaModel', 'invoice'])
            ->where('payment_method', 'reseller_balance')
            ->where('collected_by', $user->id)
            ->latest('payment_date')
            ->paginate(10);

        // Hitung total transaksi loket yang sudah diproses oleh reseller ini
        $totalTransactionsAmount = (float) \App\Models\Payment::where('payment_method', 'reseller_balance')
            ->where('collected_by', $user->id)
            ->where('status', 'verified')
            ->sum('amount');

        $totalTransactionsCount = \App\Models\Payment::where('payment_method', 'reseller_balance')
            ->where('collected_by', $user->id)
            ->where('status', 'verified')
            ->count();

        // Hitung total keuntungan tunai / fee reseller yang didapatkan reseller dari loket internet
        $totalFeeProfit = (float) \App\Models\Payment::with('customer.package')
            ->where('payment_method', 'reseller_balance')
            ->where('collected_by', $user->id)
            ->where('status', 'verified')
            ->get()
            ->sum(function($p) {
                return (float) ($p->customer?->package?->fee_reseller ?? 0);
            });

        return Inertia::render('ClientArea/Biller', [
            'reseller' => $reseller->load(['customer', 'master.customer', 'master.user']),
            'is_master' => $reseller->isMaster(),
            'downlines' => $reseller->isMaster() ? $reseller->downlines()->with(['customer', 'user'])->get()->map(function ($d) {
                return [
                    'id' => $d->id,
                    'name' => $d->customer?->name ?: $d->user?->name,
                    'phone' => $d->customer?->phone ?: $d->user?->phone,
                    'address' => $d->customer?->address ?: '-',
                    'balance' => (float) $d->balance,
                    'created_at' => $d->created_at ? $d->created_at->translatedFormat('d M Y') : '-',
                ];
            })->values()->all() : [],
            'total_downlines_balance' => $reseller->isMaster() ? (float) $reseller->downlines()->sum('balance') : 0,
            'total_override_royalty' => $reseller->isMaster() ? (float) ResellerTransaction::where('reseller_id', $reseller->id)->where('reference_id', 'like', 'OVR-%')->sum('amount') : 0,
            'min_deposit' => $reseller->isMaster() ? (int) Setting::get('master_reseller_min_deposit', 2000000) : (int) Setting::get('reseller_min_deposit', 50000),
            'override_fee_amount' => (float) ($reseller->override_fee ?: Setting::get('master_reseller_override_fee', 500)),
            'all_resellers_for_transfer' => $reseller->isMaster() ? Reseller::with(['customer', 'user'])->where('id', '!=', $reseller->id)->where('is_active', true)->where('tier', '!=', 'master')->where(function ($q) use ($reseller) { $q->where('parent_id', $reseller->id)->orWhereNull('parent_id'); })->get()->map(function ($r) use ($reseller) {
                return [
                    'id' => $r->id,
                    'name' => $r->customer?->name ?: $r->user?->name,
                    'phone' => $r->customer?->phone ?: $r->user?->phone,
                    'balance' => (float) $r->balance,
                    'is_downline' => $r->parent_id === $reseller->id,
                ];
            })->values()->all() : [],
            'recentPayments' => $recentPayments,
            'totalTransactionsAmount' => $totalTransactionsAmount,
            'totalTransactionsCount' => $totalTransactionsCount,
            'total_fee_profit' => $totalFeeProfit,
        ]);
    }

    /**
     * Cari pelanggan dan invoice internet tertunggak untuk loket pembayaran.
     */
    public function searchCustomer(Request $request)
    {
        $query = trim($request->input('q', ''));
        if (strlen($query) < 2) {
            return response()->json(['success' => true, 'customers' => []]);
        }

        $customers = \App\Models\Customer::with(['package', 'areaModel'])
            ->where(function ($q) use ($query) {
                $q->where('customer_code', 'like', "%{$query}%")
                  ->orWhere('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get();

        $result = $customers->map(function ($cust) {
            $packageFee = (float) ($cust->package?->fee_reseller ?? 0);

            // Ambil invoice internet bulanan yang belum lunas
            $unpaidInvoices = \App\Models\Invoice::with('payments')
                ->where('customer_id', $cust->id)
                ->where('is_reseller_balance', false)
                ->whereIn('status', ['unpaid', 'partial', 'overdue'])
                ->latest('due_date')
                ->get()
                ->map(function ($inv) use ($packageFee) {
                    $verifiedPaid = $inv->payments->where('status', 'verified')->sum('amount');
                    $remaining = max(0, (float) ($inv->amount - $verifiedPaid));
                    $modalCost = max(0, $remaining - $packageFee);
                    return [
                        'id' => $inv->id,
                        'invoice_number' => $inv->invoice_number,
                        'period_month' => $inv->period_month,
                        'period_year' => $inv->period_year,
                        'period_label' => $inv->period_label,
                        'issued_date' => $inv->issued_date ? Carbon::parse($inv->issued_date)->translatedFormat('d M Y') : '-',
                        'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->translatedFormat('d M Y') : '-',
                        'amount' => (float) $inv->amount,
                        'remaining' => $remaining,
                        'fee_reseller' => $packageFee,
                        'modal_cost' => $modalCost,
                        'status' => $inv->status,
                        'notes' => $inv->notes,
                    ];
                })
                ->filter(fn($inv) => $inv['remaining'] > 0)
                ->values();

            $identityPhotoUrl = null;
            if ($cust->identity_photo) {
                $cleanPhoto = ltrim($cust->identity_photo, '/');
                $identityPhotoUrl = \Illuminate\Support\Str::startsWith($cleanPhoto, ['http://', 'https://'])
                    ? $cleanPhoto
                    : asset('storage/' . $cleanPhoto);
            }

            return [
                'id' => $cust->id,
                'customer_code' => $cust->customer_code,
                'name' => $cust->name,
                'phone' => $cust->phone,
                'address' => $cust->address,
                'area' => $cust->areaModel?->name ?? $cust->area ?? '-',
                'identity_photo' => $cust->identity_photo,
                'identity_photo_url' => $identityPhotoUrl,
                'status' => $cust->status,
                'package_name' => $cust->package?->name ?? 'Internet Rumah',
                'package_price' => (float) ($cust->package?->price ?? $cust->base_amount ?? 0),
                'package_fee_reseller' => $packageFee,
                'unpaid_invoices' => $unpaidInvoices,
                'total_unpaid' => (float) $unpaidInvoices->sum('remaining'),
            ];
        });

        return response()->json([
            'success' => true,
            'customers' => $result,
        ]);
    }

    /**
     * Bayar tagihan internet pelanggan menggunakan saldo modal reseller.
     */
    public function payCustomerInvoice(Request $request, Invoice $invoice)
    {
        $user = auth()->user();
        $reseller = $this->getOrCreateReseller($user);
        if (!$reseller) {
            return back()->with('error', 'Akun Anda tidak memiliki hak akses sebagai reseller.');
        }

        // Invoice harus berupa invoice internet pelanggan (bukan invoice reseller sendiri)
        if ($invoice->is_reseller_balance) {
            return back()->with('error', 'Invoice kasbon saldo tidak dapat dibayar melalui loket ini.');
        }

        if ($invoice->status === 'paid') {
            return back()->with('info', 'Tagihan ini sudah lunas sebelumnya.');
        }

        $customer = $invoice->customer;
        if (!$customer) {
            return back()->with('error', 'Data pelanggan untuk tagihan ini tidak ditemukan.');
        }
        $customer->loadMissing(['areaModel', 'package']);

        // Hitung sisa tagihan yang harus dibayar pelanggan
        $verifiedPaid = $invoice->payments()->where('status', 'verified')->sum('amount');
        $invoiceRemaining = max(0, (float) ($invoice->amount - $verifiedPaid));

        if ($invoiceRemaining <= 0) {
            $invoice->update(['status' => 'paid']);
            return back()->with('info', 'Tagihan ini sudah lunas.');
        }

        // Ambil Fee Reseller/Mitra dari paket internet (Hanya berfungsi saat pembayaran via reseller)
        $feeReseller = (float) ($customer->package?->fee_reseller ?? 0);
        // Reseller hanya membayar harga modal: Tagihan - Fee Reseller
        $costToDeduct = max(0, $invoiceRemaining - $feeReseller);

        // Validasi kecukupan saldo modal reseller
        if ((float) $reseller->balance < $costToDeduct) {
            $kurang = $costToDeduct - (float) $reseller->balance;
            return back()->with('error', 'Saldo dompet Anda tidak mencukupi! Butuh modal Rp ' . number_format($costToDeduct, 0, ',', '.') . ' (Tagihan Rp ' . number_format($invoiceRemaining, 0, ',', '.') . ' - Fee Reseller Rp ' . number_format($feeReseller, 0, ',', '.') . '). Saldo saat ini: Rp ' . number_format($reseller->balance, 0, ',', '.') . '. Kurang Rp ' . number_format($kurang, 0, ',', '.') . '. Silakan isi saldo terlebih dahulu.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // 1. Potong saldo modal reseller seharga modal (Tagihan - Fee Reseller)
            $reseller->balance -= $costToDeduct;
            $reseller->save();

            $refNumber = 'LOKET-' . date('YmdHis') . '-' . $reseller->id;

            // 2. Catat mutasi debet saldo reseller
            \App\Models\ResellerTransaction::create([
                'reseller_id' => $reseller->id,
                'type' => 'debit',
                'amount' => $costToDeduct,
                'description' => "Bayar Tagihan Internet: {$customer->name} ({$customer->customer_code}) - Faktur: {$invoice->invoice_number} (Tagihan: Rp " . number_format($invoiceRemaining, 0, ',', '.') . ", Fee Mitra: Rp " . number_format($feeReseller, 0, ',', '.') . ", Modal Terpotong: Rp " . number_format($costToDeduct, 0, ',', '.') . ")",
                'reference_id' => $invoice->invoice_number,
            ]);

            // 3. Buat data pembayaran terverifikasi untuk invoice pelanggan dengan nominal penuh tagihan
            $payment = \App\Models\Payment::create([
                'invoice_id' => $invoice->id,
                'customer_id' => $customer->id,
                'amount' => $invoiceRemaining,
                'payment_method' => 'reseller_balance',
                'payment_channel' => 'SALDO_RESELLER',
                'payment_date' => Carbon::now(),
                'reference_number' => $refNumber,
                'status' => 'verified',
                'notes' => "Dibayar di Loket Mitra: {$reseller->name} (User: {$user->name}) via Potong Saldo Modal [Tagihan: Rp " . number_format($invoiceRemaining, 0, ',', '.') . ", Modal: Rp " . number_format($costToDeduct, 0, ',', '.') . ", Fee: Rp " . number_format($feeReseller, 0, ',', '.') . "]",
                'collected_by' => $user->id,
            ]);

            // 4. Update status invoice menjadi lunas
            $invoice->update([
                'status' => 'paid',
                'payment_channel' => 'SALDO_RESELLER',
            ]);

            // 4b. Jika Reseller ini adalah Downline dari Master Reseller, berikan Royalti Override Komisi ke Master Reseller!
            if ($reseller->parent_id && $reseller->master && $reseller->master->is_active) {
                $master = $reseller->master;
                $overrideFee = (float) ($master->override_fee ?: Setting::get('master_reseller_override_fee', 500));
                if ($overrideFee > 0) {
                    $master->increment('balance', $overrideFee);
                    ResellerTransaction::create([
                        'reseller_id' => $master->id,
                        'type' => 'credit',
                        'amount' => $overrideFee,
                        'description' => "Royalti Transaksi Sub-Reseller (" . ($reseller->customer?->name ?: $user->name) . "): Bayar Tagihan Pelanggan {$customer->name} (Faktur: {$invoice->invoice_number})",
                        'reference_id' => 'OVR-' . $payment->id,
                    ]);
                }
            }

            // 5. Jika pelanggan sedang diisolir (suspended), otomatis aktifkan kembali & sync RADIUS
            if ($customer->status === 'suspended') {
                $customer->update(['status' => 'active']);

                if (class_exists(\App\Services\RadiusService::class)) {
                    try {
                        app(\App\Services\RadiusService::class)->syncCustomer($customer);
                    } catch (\Exception $re) {
                        \Illuminate\Support\Facades\Log::warning('[Biller] Radius sync failed: ' . $re->getMessage());
                    }
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            $receiptKtpUrl = null;
            if ($customer->identity_photo) {
                $cleanPhoto = ltrim($customer->identity_photo, '/');
                $receiptKtpUrl = \Illuminate\Support\Str::startsWith($cleanPhoto, ['http://', 'https://'])
                    ? $cleanPhoto
                    : asset('storage/' . $cleanPhoto);
            }

            return back()->with([
                'success' => "Pembayaran tagihan {$customer->name} sebesar Rp " . number_format($invoiceRemaining, 0, ',', '.') . " BERHASIL! Saldo modal terpotong Rp " . number_format($costToDeduct, 0, ',', '.') . " (Fee Keuntungan Mitra: Rp " . number_format($feeReseller, 0, ',', '.') . "). Layanan internet langsung aktif.",
                'billerReceipt' => [
                    'payment_id' => $payment->id,
                    'reference_number' => $refNumber,
                    'payment_date' => Carbon::now()->translatedFormat('d F Y H:i:s'),
                    'reseller_name' => $reseller->name ?: $user->name,
                    'customer_code' => $customer->customer_code,
                    'customer_name' => $customer->name,
                    'customer_phone' => $customer->phone,
                    'customer_address' => $customer->address,
                    'customer_area' => $customer->areaModel?->name ?? $customer->area ?? '-',
                    'identity_photo_url' => $receiptKtpUrl,
                    'package_name' => $customer->package?->name ?? 'Internet Rumah',
                    'invoice_number' => $invoice->invoice_number,
                    'period' => $invoice->period_label,
                    'amount' => $invoiceRemaining,
                    'fee_reseller' => $feeReseller,
                    'modal_cost' => $costToDeduct,
                    'admin_fee' => 2500,
                    'total_collected' => $invoiceRemaining + 2500,
                    'remaining_balance' => (float) $reseller->balance,
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('[Biller] Payment failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Transfer saldo modal dari Master Reseller ke Sub-Reseller / Downline.
     */
    public function transferBalance(Request $request)
    {
        $user = auth()->user();
        $reseller = $this->getOrCreateReseller($user);
        if (!$reseller || !$reseller->isMaster()) {
            return back()->with('error', 'Hanya Master Reseller / Distributor yang memiliki hak untuk mentransfer saldo ke loket binaan.');
        }

        $validated = $request->validate([
            'target_reseller_id' => 'required|exists:resellers,id',
            'amount' => 'required|numeric|min:10000',
            'notes' => 'nullable|string|max:255',
        ], [
            'target_reseller_id.required' => 'Pilih Sub-Reseller tujuan transfer saldo.',
            'target_reseller_id.exists' => 'Sub-Reseller tujuan tidak valid.',
            'amount.required' => 'Nominal transfer saldo wajib diisi.',
            'amount.min' => 'Nominal transfer minimal Rp 10.000.',
        ]);

        $amount = (float) $validated['amount'];
        if ((float) $reseller->balance < $amount) {
            return back()->with('error', 'Saldo modal Anda tidak mencukupi! Saldo saat ini: Rp ' . number_format($reseller->balance, 0, ',', '.') . ', dibutuhkan Rp ' . number_format($amount, 0, ',', '.'));
        }

        $target = Reseller::with(['customer', 'user'])->findOrFail($validated['target_reseller_id']);
        if ($target->id === $reseller->id) {
            return back()->with('error', 'Tidak dapat mentransfer saldo ke akun sendiri.');
        }

        if ($target->isMaster()) {
            return back()->with('error', 'Tidak dapat mentransfer saldo P2P ke sesama Master Biller.');
        }

        if ($target->parent_id && $target->parent_id !== $reseller->id) {
            return back()->with('error', 'Sub-Reseller ini terdaftar di bawah naungan Master Reseller lain.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            if (empty($target->parent_id)) {
                $target->parent_id = $reseller->id;
                $target->save();
            }

            $reseller->decrement('balance', $amount);
            $target->increment('balance', $amount);

            $ref = 'TRF-' . date('YmdHis') . '-' . $reseller->id . '-' . $target->id;
            $targetName = $target->customer?->name ?: $target->user?->name;
            $masterName = $reseller->customer?->name ?: $reseller->user?->name;
            $noteText = !empty($validated['notes']) ? " ({$validated['notes']})" : '';

            // 1. Debet Saldo Master
            ResellerTransaction::create([
                'reseller_id' => $reseller->id,
                'type' => 'debit',
                'amount' => $amount,
                'description' => "Transfer Saldo ke Sub-Reseller: {$targetName}{$noteText}",
                'reference_id' => $ref,
            ]);

            // 2. Kredit Saldo Sub-Reseller
            ResellerTransaction::create([
                'reseller_id' => $target->id,
                'type' => 'credit',
                'amount' => $amount,
                'description' => "Terima Saldo dari Master Reseller: {$masterName}{$noteText}",
                'reference_id' => $ref,
            ]);

            \Illuminate\Support\Facades\DB::commit();

            return back()->with('success', "Transfer saldo modal sebesar Rp " . number_format($amount, 0, ',', '.') . " ke loket {$targetName} BERHASIL!");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Gagal memproses transfer saldo: ' . $e->getMessage());
        }
    }
}
