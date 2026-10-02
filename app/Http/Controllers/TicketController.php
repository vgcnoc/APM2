<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with(['customer', 'assignee']);

        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('tickets_view_all')) {
            if (auth()->user()->can('tickets_view_area')) {
                $query->whereHas('customer', function($q) {
                    $q->where('area_id', auth()->user()->area_id);
                });
            } else {
                $query->where('assigned_to', auth()->id());
            }
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($qc) use ($search) {
                      $qc->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority != '') {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        
        $activeCustomers = Customer::where('status', 'active')->select('id', 'name', 'customer_code', 'address')->get();
        $technicians = User::role('teknisi')->select('id', 'name')->get();

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'customers' => $activeCustomers,
            'technicians' => $technicians,
            'filters' => $request->only(['search', 'status', 'priority'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'category' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $validated['status'] = 'open';

        Ticket::create($validated);

        return back()->with('success', 'Tiket gangguan berhasil dibuat.');
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'category' => 'sometimes|required|string',
            'priority' => 'sometimes|required|in:low,medium,high,critical',
            'status' => 'sometimes|required|in:open,in_progress,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
            'subject' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
        ]);

        if (isset($validated['status'])) {
            if ($validated['status'] === 'resolved' && $ticket->status !== 'resolved') {
                $validated['resolved_at'] = now();
            }
            if ($validated['status'] === 'closed' && $ticket->status !== 'closed') {
                $validated['closed_at'] = now();
            }
        }

        $ticket->update($validated);

        return back()->with('success', 'Tiket berhasil diupdate.');
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();
        return back()->with('success', 'Tiket berhasil dihapus.');
    }
}
