<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CustomerIsolationController extends Controller
{
    /**
     * Helper untuk mengambil data tunggakan invoice pelanggan.
     */
    public static function getCustomerIsolationData(Customer $customer, ?int $userId = null): array
    {
        $customer->loadMissing(['package', 'areaModel']);

        $unpaidInvoices = Invoice::with('payments')
            ->where('customer_id', $customer->id)
            ->where('is_reseller_balance', false)
            ->where('invoice_number', 'not like', 'INV-MIG-%')
            ->whereIn('status', ['unpaid', 'partial', 'overdue'])
            ->latest('due_date')
            ->get()
            ->map(function ($inv) {
                $verifiedPaid = $inv->payments->where('status', 'verified')->sum('amount');
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'period_label' => $inv->period_label ?: 'Tagihan Internet Bulanan',
                    'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->translatedFormat('d M Y') : '-',
                    'amount' => (float) $inv->amount,
                    'remaining' => max(0, (float) ($inv->amount - $verifiedPaid)),
                    'status' => $inv->status,
                ];
            })
            ->filter(fn($i) => $i['remaining'] > 0)
            ->values();

        $defaultBanks = [
            ['bank_name' => 'BCA', 'account_number' => '1234567890', 'account_name' => 'VIRUZS NET'],
            ['bank_name' => 'BRI', 'account_number' => '0987654321', 'account_name' => 'VIRUZS NET'],
            ['bank_name' => 'Mandiri', 'account_number' => '1122334455', 'account_name' => 'VIRUZS NET'],
        ];

        $savedBanks = json_decode(Setting::get('payment_banks', '[]'), true);
        $banks = (!empty($savedBanks) && is_array($savedBanks)) ? $savedBanks : $defaultBanks;

        $isIsolated = ($customer->status === 'suspended' || !empty($customer->billing_isolated_at) || $customer->service_status === 'isolir');

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'customer_code' => $customer->customer_code,
                'package_name' => $customer->package?->name ?? 'Internet Rumah',
                'package_speed' => $customer->package?->speed ? $customer->package->speed . ' Mbps' : 'Unlimited',
                'package_price' => (float) ($customer->package?->price ?? 0),
                'area' => $customer->areaModel?->name ?? $customer->area ?? 'Area Utama',
                'address' => $customer->address ?: 'Alamat belum dilengkapi',
                'phone' => $customer->phone,
                'email' => $customer->email,
                'status' => $customer->status,
                'is_isolated' => $isIsolated,
                'isolated_at' => $customer->billing_isolated_at ? Carbon::parse($customer->billing_isolated_at)->translatedFormat('d M Y H:i') : null,
            ],
            'invoices' => $unpaidInvoices,
            'total_unpaid' => (float) $unpaidInvoices->sum('remaining'),
            'banks' => $banks,
            'company_name' => Setting::get('company_name', config('app.name', 'VIRUZS')),
            'company_phone' => Setting::get('company_phone', '088976397034'),
            'user_id' => $userId,
        ];
    }

    /**
     * Halaman Publik / Landing Page Template Isolir
     */
    public function index(Request $request)
    {
        $q = trim($request->input('q') ?? $request->input('id') ?? $request->input('phone') ?? '');
        $customer = null;

        if (!empty($q)) {
            $customer = Customer::with(['package', 'areaModel'])
                ->where('customer_code', $q)
                ->orWhere('phone', $q)
                ->orWhere('email', $q)
                ->orWhere('customer_code', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->first();
        } elseif (Auth::check()) {
            $user = Auth::user();
            $customer = Customer::with(['package', 'areaModel'])
                ->where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();
        }

        $isolationData = null;
        if ($customer) {
            $isolationData = self::getCustomerIsolationData($customer, Auth::id());
        }

        $defaultBanks = [
            ['bank_name' => 'BCA', 'account_number' => '1234567890', 'account_name' => 'VIRUZS NET'],
            ['bank_name' => 'BRI', 'account_number' => '0987654321', 'account_name' => 'VIRUZS NET'],
            ['bank_name' => 'Mandiri', 'account_number' => '1122334455', 'account_name' => 'VIRUZS NET'],
        ];
        $savedBanks = json_decode(Setting::get('payment_banks', '[]'), true);
        $banks = (!empty($savedBanks) && is_array($savedBanks)) ? $savedBanks : $defaultBanks;

        return Inertia::render('CustomerArea/Isolated', [
            'isolationData' => $isolationData,
            'searchQuery' => $q,
            'banks' => $banks,
            'companyName' => Setting::get('company_name', config('app.name', 'VIRUZS')),
            'companyPhone' => Setting::get('company_phone', '088976397034'),
            'companyAddress' => Setting::get('company_address', 'Layanan Internet Fiber Optic Cepat & Terpercaya'),
        ]);
    }

    /**
     * Pelanggan memilih untuk tetap masuk ke Dashboard Pelanggan setelah melihat banner isolir.
     */
    public function continueLogin(Request $request)
    {
        $userId = $request->session()->get('isolated_login_user_id');

        if (!$userId) {
            return redirect()->route('login', ['portal' => 'customer'])
                ->with('error', 'Sesi login telah berakhir. Silakan masuk kembali.');
        }

        $user = User::find($userId);
        if (!$user || !$user->is_active) {
            $request->session()->forget('isolated_login_user_id');
            return redirect()->route('login', ['portal' => 'customer'])
                ->with('error', 'Akun tidak valid atau non-aktif.');
        }

        Auth::login($user, true);
        $request->session()->forget('isolated_login_user_id');
        $request->session()->put('login_portal', 'customer');
        $request->session()->regenerate();

        return redirect()->route('customer-area.dashboard');
    }
}
