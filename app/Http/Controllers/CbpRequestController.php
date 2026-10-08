<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CbpRequest;
use Inertia\Inertia;

class CbpRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = CbpRequest::with(['customer', 'technicians', 'creator'])->where('status', 'pending');

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where('cbp_number', 'like', "%{$search}%")
                ->orWhereHas('customer', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('customer_code', 'like', "%{$search}%");
                });
        }

        $cbpRequests = $query->latest('id')
            ->paginate($request->per_page ?? 15)
            ->withQueryString();

        $areas = \App\Models\Area::all();
        $customers = \App\Models\Customer::where('status', 'active')
            ->select('id', 'name', 'customer_code', 'area_id', 'phone', 'address', 'status', 'service_status', 'package_id')
            ->with('package:id,name')
            ->get();

        return Inertia::render('Tickets/Cbp', [
            'requests' => $cbpRequests,
            'areas' => $areas,
            'customers' => $customers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function jadwal(Request $request)
    {
        $query = CbpRequest::with(['customer.areaModel', 'technicians', 'creator'])->whereIn('status', ['pending', 'assigned']);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where('cbp_number', 'like', "%{$search}%")
                ->orWhereHas('customer', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('customer_code', 'like', "%{$search}%");
                });
        }

        $cbpRequests = $query->latest('id')
            ->paginate($request->per_page ?? 15)
            ->withQueryString();

        $technicians = \App\Models\User::role('teknisi')->get();

        return Inertia::render('Tickets/CbpJadwal', [
            'requests' => $cbpRequests,
            'technicians' => $technicians,
            'filters' => $request->only(['search']),
        ]);
    }

    public function laporan(Request $request)
    {
        $query = CbpRequest::with(['customer.ont', 'customer.technicianSchedules', 'technicians', 'creator'])->whereIn('status', ['assigned', 'completed']);

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where('cbp_number', 'like', "%{$search}%")
                ->orWhereHas('customer', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('customer_code', 'like', "%{$search}%");
                });
        }

        $cbpRequests = $query->latest('id')
            ->paginate($request->per_page ?? 15)
            ->withQueryString();

        $materials = \App\Models\Material::all();

        return Inertia::render('Tickets/CbpLaporan', [
            'requests' => $cbpRequests,
            'materials' => $materials,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request, \App\Services\RadiusService $radius)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'reason' => 'required|string',
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
        $validated['status'] = 'pending';

        CbpRequest::create($validated);

        return redirect()->back()->with('success', 'Data Pencabutan berhasil diekskalasi dan Pelanggan sudah di-Stop Permanen.');
    }

    public function assign(Request $request, CbpRequest $cbp)
    {
        $validated = $request->validate([
            'technicians' => 'required|array',
            'technicians.*' => 'exists:users,id',
        ]);

        $cbp->update(['status' => 'assigned']);
        $cbp->technicians()->sync($validated['technicians']);

        return redirect()->back()->with('success', 'Tugas Pencabutan berhasil ditugaskan ke Teknisi.');
    }

    public function updateStatus(Request $request, CbpRequest $cbp)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'start_time' => 'required',
            'end_time' => 'required',
            'photo' => 'nullable|image|max:2048',
            'materials' => 'nullable|array',
            'materials.*.material_id' => 'required|exists:materials,id',
            'materials.*.quantity' => 'required|numeric|min:0.01',
            'materials.*.unit' => 'nullable|string',
        ]);

        $photoPath = $cbp->photo;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('cbp_photos', 'public');
        }

        if ($cbp->status !== 'completed') {
            $validated['completed_at'] = now();

            // Handle Returned Materials
            if (!empty($validated['materials'])) {
                $transaction = \App\Models\MaterialTransaction::create([
                    'transaction_number' => 'IN-' . date('YmdHis') . rand(10, 99),
                    'type' => 'in',
                    'date' => now(),
                    'technician_name' => auth()->user()->name,
                    'purpose' => 'Pengembalian Cabut Perangkat ' . $cbp->cbp_number,
                    'user_id' => auth()->id(),
                ]);

                foreach ($validated['materials'] as $item) {
                    $material = \App\Models\Material::find($item['material_id']);
                    if ($material) {
                        \App\Models\MaterialTransactionItem::create([
                            'material_transaction_id' => $transaction->id,
                            'material_id' => $item['material_id'],
                            'quantity' => $item['quantity'],
                            'unit' => $item['unit'] ?? $material->unit,
                            'price_per_unit' => $material->price_per_unit ?? 0,
                            'total_price' => ($material->price_per_unit ?? 0) * $item['quantity'],
                        ]);
                        
                        // Restock based on categories
                        if ($material->category === 'kabel') {
                            $material->increment('stock', $item['quantity']);
                            $material->increment('total_pieces', $item['quantity']);
                        } else {
                            $material->increment('stock', $item['quantity']);
                        }
                    }
                }
            }
        }

        $cbp->update([
            'status' => 'completed',
            'notes' => $validated['notes'] ?? $cbp->notes,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'photo' => $photoPath,
            'completed_at' => $validated['completed_at'] ?? $cbp->completed_at,
        ]);

        return redirect()->back()->with('success', 'Laporan Pencabutan berhasil disimpan.');
    }
}
