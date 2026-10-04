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
        
        // Find reseller by matching customer's user_id
        $customer = \App\Models\Customer::where('user_id', $user->id)->first();
        if (!$customer || !$customer->reseller) {
            abort(403, 'Anda tidak memiliki akses sebagai reseller.');
        }

        $reseller = $customer->reseller;

        $vouchers = Voucher::where('reseller_id', $reseller->id)
            ->latest()
            ->paginate(10);

        $profiles = VoucherProfile::all();

        return Inertia::render('ClientArea/Dashboard', [
            'reseller' => $reseller->load('customer'),
            'vouchers' => $vouchers,
            'profiles' => $profiles,
        ]);
    }

    /**
     * Generate vouchers for the reseller.
     */
    public function generateVouchers(Request $request)
    {
        $user = auth()->user();
        $customer = \App\Models\Customer::where('user_id', $user->id)->first();
        
        if (!$customer || !$customer->reseller) {
            abort(403, 'Akses ditolak.');
        }

        $reseller = $customer->reseller;

        $validated = $request->validate([
            'profile_id' => 'required|exists:voucher_profiles,id',
            'qty' => 'required|integer|min:1|max:100',
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

        $vouchers = [];
        for ($i = 0; $i < $validated['qty']; $i++) {
            // Basic random generator
            $code = strtoupper(Str::random(6));
            
            $vouchers[] = [
                'code' => $code,
                'router_id' => $router->id,
                'profile_id' => $profile->id,
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
