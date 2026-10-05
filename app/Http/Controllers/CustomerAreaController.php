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
        return auth()->user()->customer;
    }

    public function dashboard()
    {
        $customer = $this->getCustomer();

        if (!$customer) {
            if (auth()->user()->reseller) {
                return redirect()->route('client-area.dashboard');
            }
            abort(403, 'Akun pelanggan tidak ditemukan.');
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
        $customer = $this->getCustomer();

        if (!$customer) {
            if (auth()->user()->reseller) {
                return redirect()->route('client-area.dashboard');
            }
            abort(403, 'Akun pelanggan tidak ditemukan.');
        }

        $invoices = Invoice::where('customer_id', $customer->id)
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
            abort(403, 'Akun pelanggan tidak ditemukan.');
        }

        $tickets = Ticket::where('customer_id', $customer->id)
            ->latest()
            ->paginate(10);

        return Inertia::render('CustomerArea/Tickets', [
            'tickets' => $tickets
        ]);
    }
}
