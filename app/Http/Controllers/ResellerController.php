<?php

namespace App\Http\Controllers;

use App\Models\Reseller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class ResellerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Reseller::with([
            'customer.areaModel', 
            'customer.package', 
            'customer.user', 
            'user',
            'master.customer',
            'master.user',
            'downlines.customer',
            'downlines.user',
        ])
        ->withCount('downlines')
        ->where(function($q) {
            $q->whereNull('customer_id')
              ->orWhereHas('customer', function($cq) {
                  $cq->where('status', 'active');
              });
        });

        if ($request->filled('tier') && in_array($request->tier, ['master', 'regular'])) {
            $query->where('tier', $request->tier);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                $q->where('referral_code', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $resellers = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total' => Reseller::count(),
            'master' => Reseller::where('tier', 'master')->count(),
            'regular' => Reseller::where('tier', 'regular')->count(),
            'total_balance' => (float) Reseller::sum('balance'),
        ];

        $masterOptions = Reseller::where('tier', 'master')
            ->with(['customer', 'user'])
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'name' => $m->customer?->name ?: $m->user?->name,
                    'referral_code' => $m->referral_code,
                ];
            });

        return Inertia::render('Resellers/Index', [
            'resellers' => $resellers,
            'stats' => $stats,
            'masterOptions' => $masterOptions,
            'filters' => $request->only(['search', 'tier'])
        ]);
    }

    /**
     * Store is no longer used directly because resellers are created via Customer Booking
     */
    public function store(Request $request)
    {
        return redirect()->route('customers.booking')->with('info', 'Silakan tambah Reseller dari menu Booking Pelanggan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'balance' => 'numeric|min:0',
            'is_active' => 'boolean',
            'tier' => 'nullable|in:regular,master',
            'parent_id' => 'nullable|exists:resellers,id',
            'override_fee' => 'nullable|numeric|min:0',
        ]);

        $oldBalance = (float)$reseller->balance;
        $newBalance = (float)$validated['balance'];
        $diff = $newBalance - $oldBalance;

        // Jika diubah menjadi master tapi belum punya referral code, buatkan otomatis
        if (isset($validated['tier']) && $validated['tier'] === 'master' && empty($reseller->referral_code)) {
            $validated['referral_code'] = Reseller::generateReferralCode();
        }

        $reseller->update($validated);

        if (abs($diff) > 0) {
            \App\Models\ResellerTransaction::create([
                'reseller_id' => $reseller->id,
                'type' => $diff > 0 ? 'credit' : 'debit',
                'amount' => abs($diff),
                'description' => 'Penyesuaian Saldo via Admin (' . ($diff > 0 ? '+' : '-') . 'Rp ' . number_format(abs($diff), 0, ',', '.') . ')',
                'reference_id' => 'ADJ-' . time(),
            ]);
        }

        return redirect()->back()->with('success', 'Data kemitraan Reseller berhasil diperbarui.');
    }

    /**
     * Ubah status Tier Reseller (Regular <-> Master)
     */
    public function toggleTier(Reseller $reseller)
    {
        if ($reseller->tier === 'master') {
            $reseller->update(['tier' => 'regular']);
            return redirect()->back()->with('success', "Status mitra {$reseller->customer?->name} diubah menjadi Reseller Biasa.");
        } else {
            $reseller->update([
                'tier' => 'master',
                'referral_code' => $reseller->referral_code ?: Reseller::generateReferralCode(),
            ]);
            return redirect()->back()->with('success', "Mitra {$reseller->customer?->name} berhasil dipromosikan menjadi Master Biller / Distributor! Kode Referral: {$reseller->referral_code}");
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reseller $reseller)
    {
        DB::beginTransaction();
        try {
            $name = $reseller->customer?->name ?? $reseller->user?->name ?? ('Reseller #' . $reseller->id);
            $reseller->delete(); // Otomatis memicu Reseller::deleting -> purgeConnectedData()

            DB::commit();
            return redirect()->back()->with('success', "Akses dan seluruh data keuangan/billing Reseller {$name} berhasil dihapus bersih.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus data reseller: ' . $e->getMessage());
        }
    }

    /**
     * Create login account for reseller
     */
    public function createAccount(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        $email = trim(strtolower($validated['email']));
        $password = $validated['password'];

        $customer = $reseller->customer;
        $name = $customer ? $customer->name : 'Reseller ' . $reseller->id;
        $phone = $customer ? $customer->phone : null;

        $existingUser = User::where('email', $email)->first();

        if (!$existingUser && $customer && $customer->user_id) {
            $existingUser = User::find($customer->user_id);
        }

        try {
            DB::transaction(function () use ($customer, $reseller, $existingUser, $email, $password, $name, $phone) {
                $role = Role::firstOrCreate(['name' => 'reseller']);

                if ($existingUser) {
                    $otherReseller = Reseller::where('user_id', $existingUser->id)
                        ->where('id', '!=', $reseller->id)
                        ->first();

                    if ($otherReseller) {
                        throw new \Exception('Email ini sudah digunakan oleh akun reseller lain.');
                    }

                    if (method_exists($existingUser, 'hasRole') && !$existingUser->hasRole('reseller')) {
                        $existingUser->assignRole($role);
                    }

                    $updateUser = ['is_active' => true];
                    if (!empty($password)) {
                        $updateUser['password'] = Hash::make($password);
                    }
                    $existingUser->update($updateUser);

                    $reseller->update(['user_id' => $existingUser->id]);

                    if ($customer && !$customer->user_id) {
                        $customer->update(['user_id' => $existingUser->id]);
                    }
                } else {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($password),
                        'phone' => $phone,
                        'role' => 'reseller',
                        'is_active' => true,
                    ]);

                    $user->assignRole($role);

                    $reseller->update(['user_id' => $user->id]);

                    if ($customer && !$customer->user_id) {
                        $customer->update(['user_id' => $user->id]);
                    }
                }
            });

            return redirect()->back()->with('success', 'Akun login reseller berhasil dibuat/ditautkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat akun: ' . $e->getMessage());
        }
    }

    /**
     * Update akun login reseller (password dan email)
     */
    public function resetPassword(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'email' => 'nullable|email|unique:users,email,' . $reseller->user_id,
            'password' => 'nullable|string|min:6',
        ]);

        if (!$reseller->user_id || !$reseller->user) {
            return redirect()->back()->with('error', 'Reseller ini belum memiliki akun.');
        }

        $userUpdates = [];
        if (!empty($validated['password'])) {
            $userUpdates['password'] = bcrypt($validated['password']);
        }
        if (!empty($validated['email'])) {
            $userUpdates['email'] = $validated['email'];
        }

        $changed = false;
        if (!empty($userUpdates)) {
            $reseller->user->update($userUpdates);
            $changed = true;
        }
        
        if ($reseller->user->role !== 'reseller') {
            $reseller->user->update(['role' => 'reseller']);
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'reseller']);
            $reseller->user->syncRoles([$role]);
            $changed = true;
        }

        if ($changed) {
            return redirect()->back()->with('success', 'Data akun login reseller berhasil diubah.');
        }

        return redirect()->back()->with('info', 'Tidak ada perubahan pada akun login.');
    }
}
