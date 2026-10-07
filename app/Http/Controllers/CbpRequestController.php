<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CbpRequest;
use Inertia\Inertia;

class CbpRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = CbpRequest::with(['customer', 'assignee', 'creator']);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where('cbp_number', 'like', "%{$search}%")
                ->orWhereHas('customer', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('customer_code', 'like', "%{$search}%");
                });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $cbpRequests = $query->latest('id')
            ->paginate($request->per_page ?? 15)
            ->withQueryString();

        $technicians = \App\Models\User::role('technician')->get();

        return Inertia::render('Tickets/Cbp', [
            'requests' => $cbpRequests,
            'technicians' => $technicians,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request, \App\Services\RadiusService $radius)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'reason' => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $customer = \App\Models\Customer::findOrFail($validated['customer_id']);

        // Stop Permanen the customer
        if ($customer->status !== 'terminated') {
            $customer->update(['status' => 'terminated']);
            $radius->guard(fn($r) => $r->syncCustomer($customer));
            $accounts = $radius->customerAccounts($customer);
            foreach (array_keys($accounts) as $username) {
                $radius->guard(fn($r) => $r->disconnect($username));
            }
        }

        $validated['created_by'] = auth()->id();
        if (!empty($validated['assigned_to'])) {
            $validated['status'] = 'assigned';
        }

        CbpRequest::create($validated);

        return redirect()->back()->with('success', 'Data Pencabutan berhasil dibuat dan Pelanggan sudah di-Stop Permanen.');
    }

    public function assign(Request $request, CbpRequest $cbp)
    {
        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $cbp->update([
            'assigned_to' => $validated['assigned_to'],
            'status' => 'assigned',
        ]);

        return redirect()->back()->with('success', 'Tugas Pencabutan berhasil ditugaskan ke Teknisi.');
    }

    public function updateStatus(Request $request, CbpRequest $cbp)
    {
        $validated = $request->validate([
            'status' => 'required|in:assigned,completed,canceled',
            'notes' => 'nullable|string',
        ]);

        if ($validated['status'] === 'completed' && $cbp->status !== 'completed') {
            $validated['completed_at'] = now();
        }

        $cbp->update($validated);

        return redirect()->back()->with('success', 'Status Pencabutan berhasil diperbarui.');
    }
}
