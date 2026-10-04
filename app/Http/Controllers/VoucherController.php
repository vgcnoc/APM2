<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\VoucherProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = Voucher::with('profile');

        if ($request->filled('profile_id')) {
            $query->where('voucher_profile_id', $request->profile_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $vouchers = $query->latest()->paginate(20)->withQueryString();
        $profiles = VoucherProfile::all();

        return Inertia::render('Vouchers/Index', [
            'vouchers' => $vouchers,
            'profiles' => $profiles,
            'filters' => $request->only(['search', 'profile_id', 'status'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'voucher_profile_id' => 'required|exists:voucher_profiles,id',
            'amount' => 'required|integer|min:1|max:500',
            'length' => 'required|integer|min:4|max:12',
            'prefix' => 'nullable|string|max:4',
            'type' => 'required|in:up,vc', // up = user & password, vc = code only (user=pass=code)
        ]);

        $profile = VoucherProfile::find($validated['voucher_profile_id']);
        
        $vouchers = [];
        $now = now();
        
        for ($i = 0; $i < $validated['amount']; $i++) {
            $code = strtoupper($validated['prefix'] . Str::random($validated['length']));
            
            // Ensure unique code
            while(Voucher::where('code', $code)->exists()) {
                $code = strtoupper($validated['prefix'] . Str::random($validated['length']));
            }
            
            if ($validated['type'] === 'vc') {
                $username = $code;
                $password = $code;
            } else {
                $username = $code;
                $password = Str::random($validated['length']);
            }

            $vouchers[] = [
                'voucher_profile_id' => $profile->id,
                'code' => $code,
                'username' => $username,
                'password' => $password,
                'status' => 'available',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Voucher::insert($vouchers);

        return redirect()->back()->with('success', $validated['amount'] . ' Voucher berhasil di-generate.');
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();
        return redirect()->back()->with('success', 'Voucher berhasil dihapus.');
    }
    
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:vouchers,id',
        ]);
        
        Voucher::whereIn('id', $validated['ids'])->delete();
        
        return redirect()->back()->with('success', count($validated['ids']) . ' Voucher berhasil dihapus.');
    }

    public function print(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:vouchers,id',
        ]);
        
        $vouchers = Voucher::with('profile')->whereIn('id', $validated['ids'])->get();
        
        return view('vouchers.print', compact('vouchers'));
    }
}
