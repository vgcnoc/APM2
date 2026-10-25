<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Reseller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerAreaController extends Controller
{
    /**
     * Dapatkan profil customer yang tertaut dengan user login.
     */
    private function getCustomer()
    {
        $user = auth()->user();
        if (!$user) {
            return null;
        }

        // 1. Relasi langsung user->customer
        if ($user->customer) {
            return $user->customer;
        }

        // 2. Query langsung via user_id
        $customer = Customer::where('user_id', $user->id)->first();
        if ($customer) {
            return $customer;
        }

        // 3. Fallback matching via email jika user_id belum terhubung
        if (!empty($user->email)) {
            $customer = Customer::where('email', $user->email)->first();
            if ($customer) {
                // Auto-link user_id agar request berikutnya instan
                $customer->update(['user_id' => $user->id]);
                return $customer;
            }
        }

        // 4. Cek jika terhubung via Akun Reseller
        $reseller = Reseller::where('user_id', $user->id)->first();
        if ($reseller && $reseller->customer) {
            return $reseller->customer;
        }

        return null;
    }

    /**
     * Periksa apakah user memiliki peran staf/admin
     */
    private function isStaffUser($user): bool
    {
        if (!$user) return false;
        $staffRoles = ['admin', 'superadmin', 'super admin', 'dispatcher', 'auditor', 'management'];
        if (in_array(strtolower($user->role ?? ''), $staffRoles)) {
            return true;
        }
        if (method_exists($user, 'hasRole') && $user->hasRole($staffRoles)) {
            return true;
        }
        return false;
    }

    public function dashboard()
    {
        $user = auth()->user();
        $customer = $this->getCustomer();

        // Hanya jika user TIDAK memiliki data langganan pelanggan, tetapi memiliki akun reseller, alihkan ke portal reseller
        if (!$customer && !$this->isStaffUser($user) && ($user->role === 'reseller' || Reseller::where('user_id', $user->id)->exists())) {
            return redirect()->route('client-area.dashboard');
        }

        if (!$customer) {
            if ($this->isStaffUser($user)) {
                $customer = Customer::with(['package', 'ont', 'areaModel'])->first();
            }
            if (!$customer) {
                // Mencegah redirect loop tak berujung jika user adalah pelanggan tanpa data customer
                return Inertia::render('CustomerArea/NoCustomer', [
                    'user' => $user
                ]);
            }
        }

        // Ambil invoice tertunggak paling mutakhir
        $latestInvoice = Invoice::where('customer_id', $customer->id)
            ->whereIn('status', ['unpaid', 'overdue', 'partial'])
            ->latest('due_date')
            ->first();

        // Ambil riwayat tiket gangguan terkini
        $recentTickets = Ticket::where('customer_id', $customer->id)
            ->latest()
            ->limit(5)
            ->get();
            
        // Load relasi paket, ONT, dan area pelanggan
        $customer->load(['package', 'ont', 'areaModel']);

        $isReseller = ($user->role === 'reseller')
            || (method_exists($user, 'hasRole') && $user->hasRole('reseller'))
            || Reseller::where('user_id', $user->id)->exists();

        return Inertia::render('CustomerArea/Dashboard', [
            'customer' => $customer,
            'latestInvoice' => $latestInvoice,
            'recentTickets' => $recentTickets,
            'isReseller' => $isReseller,
        ]);
    }

    public function billing()
    {
        $user = auth()->user();
        $customer = $this->getCustomer();

        if (!$customer && !$this->isStaffUser($user) && ($user->role === 'reseller' || Reseller::where('user_id', $user->id)->exists())) {
            return redirect()->route('client-area.billing');
        }

        if (!$customer) {
            if ($this->isStaffUser($user)) {
                $customer = Customer::with('package')->first();
            }
            if (!$customer) {
                return Inertia::render('CustomerArea/NoCustomer', [
                    'user' => $user
                ]);
            }
        }

        $invoices = Invoice::where('customer_id', $customer->id)
            ->where('is_reseller_balance', false)
            ->latest('id')
            ->paginate(10);

        return Inertia::render('CustomerArea/Billing', [
            'customer' => $customer,
            'invoices' => $invoices
        ]);
    }

    public function tickets()
    {
        $user = auth()->user();
        $customer = $this->getCustomer();

        if (!$customer && !$this->isStaffUser($user) && ($user->role === 'reseller' || Reseller::where('user_id', $user->id)->exists())) {
            return redirect()->route('client-area.dashboard');
        }

        $customer = $this->getCustomer();

        if (!$customer) {
            if ($this->isStaffUser($user)) {
                $customer = Customer::with('package')->first();
            }
            if (!$customer) {
                return Inertia::render('CustomerArea/NoCustomer', [
                    'user' => $user
                ]);
            }
        }

        $tickets = Ticket::where('customer_id', $customer->id)
            ->latest()
            ->paginate(10);

        return Inertia::render('CustomerArea/Tickets', [
            'customer' => $customer,
            'tickets' => $tickets
        ]);
    }

    /**
     * Pelanggan membuat tiket keluhan / gangguan baru dari portal
     */
    public function storeTicket(Request $request)
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return back()->with('error', 'Profil data pelanggan Anda belum terhubung. Silakan hubungi CS.');
        }

        $validated = $request->validate([
            'category' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|in:low,medium,high,critical',
        ]);

        $validated['customer_id'] = $customer->id;
        $validated['status'] = 'open';
        $validated['priority'] = $validated['priority'] ?? 'medium';

        Ticket::create($validated);

        return redirect()->route('customer-area.tickets')->with('success', 'Laporan tiket gangguan berhasil dibuat. Tim kami akan segera menindaklanjuti.');
    }
}
