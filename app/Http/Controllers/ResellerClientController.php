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
     * Dapatkan data reseller atau inisialisasi otomatis untuk admin / role reseller.
     */
    private function getOrCreateReseller($user)
    {
        if (!$user) return null;

        $reseller = \App\Models\Reseller::where('user_id', $user->id)->first();
        if (!$reseller) {
            if (in_array($user->role, ['admin', 'superadmin', 'reseller']) || (method_exists($user, 'hasRole') && $user->hasRole('admin'))) {
                $reseller = \App\Models\Reseller::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'customer_id' => $user->customer?->id ?? null,
                        'balance' => in_array($user->role, ['admin', 'superadmin']) ? 5000000 : 0,
                        'is_active' => true,
                    ]
                );
            }
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
            ->latest()
            ->paginate(10);

        $profiles = $user->voucherProfiles()->where('is_customer_free', false)->get();
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
        $reseller = $this->getOrCreateReseller($user);
        
        if (!$reseller) {
            return redirect()->back()->with('error', 'Akun Anda tidak memiliki hak akses sebagai reseller.');
        }

        $validated = $request->validate([
            'profile_id' => 'required|exists:voucher_profiles,id',
            'qty' => 'required|integer|min:1|max:100',
            'combination' => 'nullable|string|in:alphanumeric,numeric,alpha',
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
            'reseller' => $reseller->load('customer'),
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
}
