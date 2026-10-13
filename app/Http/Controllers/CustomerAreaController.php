<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerAreaController extends Controller
{
    /**
     * Get the customer profile linked to the logged in user.
     */
    private function getCustomer()
    {
        $user = auth()->user();
        if ($user->customer) {
            return $user->customer;
        }
        $reseller = \App\Models\Reseller::where('user_id', $user->id)->first();
        if ($reseller && $reseller->customer) {
            return $reseller->customer;
        }
        return null;
    }

    public function dashboard()
    {
        $user = auth()->user();
        if ($user->role === 'reseller' || \App\Models\Reseller::where('user_id', $user->id)->exists()) {
            return redirect()->route('client-area.dashboard');
        }

        $customer = $this->getCustomer();

        if (!$customer) {
            if (in_array($user->role, ['admin', 'superadmin'])) {
                $customer = Customer::with('package')->first();
            }
            if (!$customer) {
                return redirect()->route('dashboard')->with('error', 'Akun pelanggan tidak ditemukan.');
            }
        }

        // Get latest unpaid invoice
        $latestInvoice = Invoice::where('customer_id', $customer->id)
            ->whereIn('status', ['unpaid', 'overdue'])
            ->latest('due_date')
            ->first();

        // Get recent tickets
        $recentTickets = Ticket::where('customer_id', $customer->id)
            ->latest()
            ->limit(3)
            ->get();
            
        // Package data
        $customer->load('package');

        return Inertia::render('CustomerArea/Dashboard', [
            'customer' => $customer,
            'latestInvoice' => $latestInvoice,
            'recentTickets' => $recentTickets
        ]);
    }

    public function billing()
    {
        $user = auth()->user();
        if ($user->role === 'reseller' || \App\Models\Reseller::where('user_id', $user->id)->exists()) {
            return redirect()->route('client-area.billing');
        }

        $customer = $this->getCustomer();

        if (!$customer) {
            if (in_array($user->role, ['admin', 'superadmin'])) {
                $customer = Customer::with('package')->first();
            }
            if (!$customer) {
                return redirect()->route('dashboard')->with('error', 'Akun pelanggan tidak ditemukan.');
            }
        }

        $invoices = Invoice::where('customer_id', $customer->id)
            ->where('is_reseller_balance', false)
            ->latest('due_date')
            ->paginate(10);

        return Inertia::render('CustomerArea/Billing', [
            'invoices' => $invoices
        ]);
    }

    public function tickets()
    {
        $customer = $this->getCustomer();

        if (!$customer) {
            if (auth()->user()->reseller) {
                return redirect()->route('client-area.dashboard');
            }
            if (in_array(auth()->user()->role, ['admin', 'superadmin'])) {
                $customer = Customer::with('package')->first();
            }
            if (!$customer) {
                return redirect()->route('dashboard')->with('error', 'Akun pelanggan tidak ditemukan.');
            }
        }

        $tickets = Ticket::where('customer_id', $customer->id)
            ->latest()
            ->paginate(10);

        return Inertia::render('CustomerArea/Tickets', [
            'tickets' => $tickets
        ]);
    }
}
