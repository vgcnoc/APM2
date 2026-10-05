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
        $query = Reseller::with(['customer.areaModel', 'customer.package', 'customer.user'])
            ->whereHas('customer', function($q) {
                $q->where('status', 'active');
            });

        if ($request->has('search')) {
            $query->whereHas('customer', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_code', 'like', '%' . $request->search . '%');
            });
        }

        $resellers = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Resellers/Index', [
            'resellers' => $resellers,
            'filters' => $request->only(['search'])
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
        ]);

        $reseller->update($validated);

        return redirect()->back()->with('success', 'Data dompet Reseller berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reseller $reseller)
    {
        // Only removes the reseller profile, not the customer
        $customer = $reseller->customer;
        $reseller->delete();
        
        if ($customer) {
            $customer->update(['is_reseller' => false]);
        }

        return redirect()->back()->with('success', 'Akses Reseller berhasil dicabut.');
    }

    /**
     * Create login account for reseller
     */
    public function createAccount(Request $request, Reseller $reseller)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8'
        ]);

        $customer = $reseller->customer;

        if (!$customer) {
            return redirect()->back()->with('error', 'Data pelanggan tidak ditemukan.');
        }

        if ($customer->user_id) {
            return redirect()->back()->with('error', 'Reseller ini sudah memiliki akun.');
        }

        try {
            DB::transaction(function () use ($customer, $validated) {
                // Update customer email if different
                if ($customer->email !== $validated['email']) {
                    $customer->update(['email' => $validated['email']]);
                }

                // Ensure reseller role exists
                $role = Role::firstOrCreate(['name' => 'reseller']);

                $user = User::create([
                    'name' => $customer->name,
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'phone' => $customer->phone,
                    'role' => 'reseller',
                    'is_active' => true,
                ]);

                $user->assignRole($role);

                $customer->update(['user_id' => $user->id]);
            });

            return redirect()->back()->with('success', 'Akun reseller berhasil dibuat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat akun: ' . $e->getMessage());
        }
    }
}
