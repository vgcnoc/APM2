<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Odp;
use App\Models\Ont;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Daftar semua pelanggan (dengan filter & pencarian)
     */
    public function index(Request $request): Response
    {
        $customers = Customer::with(['package', 'ont.odp'])
            ->search($request->search)
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->package_id, fn ($q, $pkg) => $q->where('package_id', $pkg))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 15)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'packages' => InternetPackage::active()->get(),
            'filters' => $request->only(['search', 'status', 'package_id']),
            'statusOptions' => [
                'booking' => 'Booking',
                'survey' => 'Survey',
                'installing' => 'Proses Pasang',
                'active' => 'Aktif',
                'suspended' => 'Suspended',
                'terminated' => 'Terminated',
            ],
        ]);
    }

    /**
     * Halaman Data Booking (status = booking)
     */
    public function booking(Request $request): Response
    {
        $customers = Customer::booking()
            ->search($request->search)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Booking', [
            'customers' => $customers,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Halaman Survey (status = survey)
     */
    public function survey(Request $request): Response
    {
        $customers = Customer::survey()
            ->with(['surveys.odp', 'surveys.surveyor'])
            ->search($request->search)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $availableOdps = Odp::active()->hasAvailablePort()
            ->with('odc.olt')
            ->get();

        return Inertia::render('Customers/Survey', [
            'customers' => $customers,
            'availableOdps' => $availableOdps,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Halaman Pasang (status = installing / active)
     */
    public function installed(Request $request): Response
    {
        $customers = Customer::installed()
            ->with(['package', 'ont.odp.odc.olt'])
            ->search($request->search)
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Installed', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Form tambah pelanggan baru (booking)
     */
    public function create(): Response
    {
        return Inertia::render('Customers/Create', [
            'packages' => InternetPackage::active()->get(),
        ]);
    }

    /**
     * Simpan pelanggan baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'package_id' => 'nullable|exists:internet_packages,id',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = 'booking';

        Customer::create($validated);

        return redirect()->route('customers.booking')
            ->with('success', 'Data booking pelanggan berhasil ditambahkan.');
    }

    /**
     * Detail pelanggan (profil lengkap + ONT + ODP)
     */
    public function show(Customer $customer): Response
    {
        $customer->load([
            'package',
            'ont.odp.odc.olt',
            'invoices' => fn ($q) => $q->orderByDesc('period_year')->orderByDesc('period_month')->limit(12),
            'invoices.payments',
            'tickets' => fn ($q) => $q->orderByDesc('created_at')->limit(10),
            'tickets.assignee',
            'surveys.odp',
            'surveys.surveyor',
        ]);

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
        ]);
    }

    /**
     * Form edit pelanggan
     */
    public function edit(Customer $customer): Response
    {
        $customer->load(['ont.odp', 'package']);

        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
            'packages' => InternetPackage::active()->get(),
            'availableOdps' => Odp::active()->hasAvailablePort()->with('odc.olt')->get(),
        ]);
    }

    /**
     * Update data pelanggan
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'package_id' => 'nullable|exists:internet_packages,id',
            'status' => ['required', Rule::in(['booking', 'survey', 'installing', 'active', 'suspended', 'terminated'])],
            'notes' => 'nullable|string',
        ]);

        // Jika status berubah ke 'active', set activation_date
        if ($validated['status'] === 'active' && $customer->status !== 'active') {
            $validated['activation_date'] = now()->toDateString();
        }

        $customer->update($validated);

        return redirect()->route('customers.show', $customer)
            ->with('success', 'Data pelanggan berhasil diperbarui.');
    }

    /**
     * Hapus data pelanggan
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        DB::transaction(function () use ($customer) {
            // Lepaskan ONT jika ada
            if ($customer->ont) {
                $odp = $customer->ont->odp;
                $customer->ont->update(['customer_id' => null, 'status' => 'inactive']);

                // Kurangi used_ports di ODP
                if ($odp) {
                    $odp->decrement('used_ports');
                }
            }

            $customer->delete();
        });

        return redirect()->route('customers.index')
            ->with('success', 'Data pelanggan berhasil dihapus.');
    }

    /**
     * Assign ONT ke pelanggan (integrasi Customer ↔ ONT ↔ ODP)
     */
    public function assignOnt(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'odp_id' => 'required|exists:odps,id',
            'port_number' => 'required|integer|min:1',
            'serial_number' => 'required|string|unique:onts,serial_number',
            'mac_address' => 'nullable|string|max:17',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'rx_power' => 'nullable|numeric',
            'tx_power' => 'nullable|numeric',
        ]);

        DB::transaction(function () use ($validated, $customer) {
            // Buat ONT baru
            $ont = Ont::create([
                'odp_id' => $validated['odp_id'],
                'customer_id' => $customer->id,
                'serial_number' => $validated['serial_number'],
                'mac_address' => $validated['mac_address'] ?? null,
                'brand' => $validated['brand'] ?? null,
                'model' => $validated['model'] ?? null,
                'port_number' => $validated['port_number'],
                'rx_power' => $validated['rx_power'] ?? null,
                'tx_power' => $validated['tx_power'] ?? null,
                'status' => 'active',
            ]);

            // Update used_ports di ODP
            $odp = Odp::find($validated['odp_id']);
            $odp->increment('used_ports');

            // Jika ODP penuh, update statusnya
            if ($odp->used_ports >= $odp->total_ports) {
                $odp->update(['status' => 'full']);
            }

            // Update status pelanggan ke active
            $customer->update([
                'status' => 'active',
                'activation_date' => now()->toDateString(),
            ]);
        });

        return redirect()->route('customers.show', $customer)
            ->with('success', 'ONT berhasil dipasang dan pelanggan diaktifkan.');
    }
}
