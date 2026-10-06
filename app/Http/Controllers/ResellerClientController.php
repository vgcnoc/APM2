<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\VoucherProfile;
use App\Models\Voucher;
use App\Models\Router;
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

        $profiles = VoucherProfile::all();

        // Calculate Total Income
        $income = Voucher::where('reseller_id', $reseller->id)
            ->join('voucher_profiles', 'vouchers.voucher_profile_id', '=', 'voucher_profiles.id')
            ->sum('voucher_profiles.price');

        return Inertia::render('ClientArea/Dashboard', [
            'reseller' => $reseller->load('customer'),
            'vouchers' => $vouchers,
            'profiles' => $profiles,
            'income' => $income,
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

        $profile = VoucherProfile::find($validated['profile_id']);
        
        // Price for reseller calculation
        // For simplicity, we just deduct profile->price * qty for now
        $totalCost = $profile->price * $validated['qty'];
        
        if ($reseller->balance < $totalCost) {
            return redirect()->back()->withErrors(['error' => 'Saldo tidak mencukupi untuk generate ' . $validated['qty'] . ' voucher.']);
        }

        // Deduct balance
        $reseller->balance -= $totalCost;
        $reseller->save();

        // Generate vouchers
        $router = Router::where('is_active', true)->first();
        if (!$router) {
            return redirect()->back()->withErrors(['error' => 'Router aktif tidak ditemukan.']);
        }

        $combination = $validated['combination'] ?? 'alphanumeric';
        
        $vouchers = [];
        for ($i = 0; $i < $validated['qty']; $i++) {
            if ($combination === 'numeric') {
                $code = substr(str_shuffle(str_repeat('0123456789', 5)), 0, 6);
            } elseif ($combination === 'alpha') {
                $code = substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 5)), 0, 6);
            } else {
                $code = strtoupper(Str::random(6));
            }
            
            $vouchers[] = [
                'code' => $code,
                'username' => $code,
                'password' => $code,
                'router_id' => $router->id,
                'voucher_profile_id' => $profile->id,
                'reseller_id' => $reseller->id,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Voucher::insert($vouchers);

        return redirect()->back()->with('success', $validated['qty'] . ' Voucher berhasil digenerate. Saldo Anda terpotong Rp ' . number_format($totalCost, 0, ',', '.'));
    }
}
