<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Odp;
use App\Models\Ont;
use App\Models\Survey;
use App\Models\TechnicianSchedule;
use App\Models\User;
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
        $query = Customer::booking()
            ->when($request->date, function ($q, $date) {
                $q->whereDate('created_at', $date);
            })
            ->when($request->area, function ($q, $area) {
                $q->where(function($sub) use ($area) {
                    $sub->where('area', $area); // Since it's a dropdown, exact match is better
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->search($request->search);

        $customers = (clone $query)
            ->with('package')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        // Get all areas for dropdown
        $areas = \App\Models\Area::orderBy('name')->pluck('name')->toArray();

        // Calculate statistics based on the current filtered view
        $stats = [
            'total' => (clone $query)->count(),
            'baru' => (clone $query)->where('status', 'booking')->count(),
            'disurvey' => (clone $query)->where('status', 'survey')->count(),
        ];

        return Inertia::render('Customers/Booking', [
            'customers' => $customers,
            'areas' => $areas,
            'stats' => $stats,
            'filters' => $request->only(['search', 'date', 'area', 'status']),
        ]);
    }

    /**
     * Halaman Survey (status = survey)
     */
    public function survey(Request $request): Response
    {
        $tab = $request->tab ?? 'semua';
        
        $baseQuery = Customer::survey()
            ->with(['surveys.odp', 'surveys.surveyor', 'technicianSchedules.technician'])
            ->when($request->technician_id, function ($q, $techId) {
                $q->where(function ($sub) use ($techId) {
                    $sub->whereHas('technicianSchedules', function ($sq) use ($techId) {
                        $sq->where('technician_id', $techId);
                    })->orWhereHas('surveys', function ($sq) use ($techId) {
                        $sq->where('surveyor_id', $techId);
                    });
                });
            })
            ->when($request->date, function ($q, $date) {
                $q->whereDate('created_at', $date);
            })
            ->when($request->area, function ($q, $area) {
                $q->where('area', $area);
            })
            ->search($request->search);

        // Calculate statistics based on the base query
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'jadwalkan' => (clone $baseQuery)->where('status', 'survey')->doesntHave('surveys')->doesntHave('technicianSchedules')->count(),
            'laporan' => (clone $baseQuery)->where('status', 'survey')->doesntHave('surveys')->has('technicianSchedules')->count(),
            'ready' => (clone $baseQuery)->where('status', 'survey')->whereHas('surveys', function ($sq) {
                $sq->where('feasibility', 'feasible');
            })->count(),
            'unfeasible' => (clone $baseQuery)->whereHas('surveys', function ($sq) {
                $sq->where('feasibility', 'not_feasible');
            })->count(),
        ];

        $customers = (clone $baseQuery)
            ->when($tab === 'jadwalkan', function ($q) {
                $q->where('status', 'survey')->doesntHave('surveys')->doesntHave('technicianSchedules');
            })
            ->when($tab === 'laporan', function ($q) {
                $q->where('status', 'survey')->doesntHave('surveys')->has('technicianSchedules');
            })
            ->when($tab === 'ready', function ($q) {
                $q->where('status', 'survey')->whereHas('surveys', function ($sq) {
                    $sq->where('feasibility', 'feasible');
                });
            })
            ->when($tab === 'unfeasible', function ($q) {
                $q->whereHas('surveys', function ($sq) {
                    $sq->where('feasibility', 'not_feasible');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $availableOdps = Odp::active()
            ->with('odc.olt')
            ->get();
            
        $technicians = User::where('role', 'teknisi')->where('is_active', true)->get();

        // Get all areas for dropdown
        $areas = \App\Models\Area::orderBy('name')->pluck('name')->toArray();

        return Inertia::render('Customers/Survey', [
            'customers' => $customers,
            'availableOdps' => $availableOdps,
            'technicians' => $technicians,
            'areas' => $areas,
            'stats' => $stats,
            'filters' => $request->only(['search', 'tab', 'technician_id', 'date', 'area']),
        ]);
    }

    /**
     * Halaman Pasang (status = installing / active)
     */
    public function installed(Request $request): Response
    {
        $customers = Customer::installed()
            ->with(['package', 'ont.odp.odc.olt', 'technicianSchedules' => function ($q) {
                $q->where('type', 'installation')->with('technician');
            }])
            ->search($request->search)
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $technicians = User::where('role', 'teknisi')->where('is_active', true)->get();
        $availableOnts = \App\Models\Ont::where('status', 'Sudah Set')
            ->whereNull('customer_id')
            ->get();
            
        $materialTransactions = \App\Models\MaterialTransaction::where('type', 'out')
            ->with('items.material')
            ->latest()
            ->limit(100)
            ->get();

        return Inertia::render('Customers/Installed', [
            'customers' => $customers,
            'technicians' => $technicians,
            'availableOnts' => $availableOnts,
            'materialTransactions' => $materialTransactions,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Form tambah pelanggan baru (booking)
     */
    public function create(): Response
    {
        $areas = \App\Models\Area::pluck('name');
        
        return Inertia::render('Customers/Create', [
            'packages' => InternetPackage::active()->get(),
            'sales' => User::where('role', 'sales')->get(),
            'areas' => $areas,
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
            'area' => 'nullable|string',
            'identity_photo' => 'nullable|image|max:5120', // Max 5MB
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'package_id' => 'nullable|exists:internet_packages,id',
            'notes' => 'nullable|string',
            'base_amount' => 'required|numeric|min:0',
            'registration_date' => 'required|date',
            'installation_fee' => 'nullable|numeric|min:0',
            'sales_id' => 'nullable|exists:users,id',
        ]);

        if ($request->hasFile('identity_photo')) {
            $validated['identity_photo'] = $request->file('identity_photo')->store('ktp', 'public');
        }

        // Auto-create new area if not exists in master data
        if (!empty($validated['area'])) {
            \App\Models\Area::firstOrCreate(['name' => $validated['area']]);
        }

        $validated['status'] = 'booking'; // Ensure it goes to booking

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
            'technicianSchedules.technician',
        ]);

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
            'availableOdps' => Odp::active()->hasAvailablePort()->with('odc.olt')->get(),
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

        return redirect()->back()
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
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'photo_odp' => 'nullable|image|max:5120',
            'photo_installation' => 'nullable|image|max:5120',
            'photo_ont' => 'nullable|image|max:5120',
            'photo_customer' => 'nullable|image|max:5120',
            'photo_redaman' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('photo_odp')) $validated['photo_odp'] = $request->file('photo_odp')->store('installations', 'public');
        if ($request->hasFile('photo_installation')) $validated['photo_installation'] = $request->file('photo_installation')->store('installations', 'public');
        if ($request->hasFile('photo_ont')) $validated['photo_ont'] = $request->file('photo_ont')->store('installations', 'public');
        if ($request->hasFile('photo_customer')) $validated['photo_customer'] = $request->file('photo_customer')->store('installations', 'public');
        if ($request->hasFile('photo_redaman')) $validated['photo_redaman'] = $request->file('photo_redaman')->store('installations', 'public');

        DB::transaction(function () use ($validated, $customer) {
            // Buat ONT baru
            $ont = Ont::create([
                'odp_id' => $validated['odp_id'],
                'customer_id' => $customer->id,
                'serial_number' => 'SN-' . strtoupper(\Illuminate\Support\Str::random(8)),
                'port_number' => $validated['port_number'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'photo_odp' => $validated['photo_odp'] ?? null,
                'photo_installation' => $validated['photo_installation'] ?? null,
                'photo_ont' => $validated['photo_ont'] ?? null,
                'photo_customer' => $validated['photo_customer'] ?? null,
                'photo_redaman' => $validated['photo_redaman'] ?? null,
                'status' => 'active',
            ]);

            // Update used_ports di ODP
            $odp = Odp::find($validated['odp_id']);
            $odp->increment('used_ports');

            // Jika ODP penuh, update statusnya
            if ($odp->used_ports >= $odp->total_ports) {
                $odp->update(['status' => 'full']);
            }

            // Update status jadwal teknisi ke done jika ada
            $schedule = $customer->technicianSchedules()->where('type', 'installation')->where('status', 'scheduled')->first();
            if ($schedule) {
                $schedule->update(['status' => 'done']);
            }
        });

        return redirect()->route('customers.installed')
            ->with('success', 'Instalasi selesai. Menunggu audit dan aktivasi.');
    }

    /**
     * Aktivasi pelanggan setelah audit selesai
     */
    public function activate(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'activation_date' => 'required|date',
            'pppoe_user' => 'nullable|string',
            'pppoe_password' => 'nullable|string',
            'vlan_mode' => 'nullable|string',
            'vlan_id' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($customer->status === 'installing' && $customer->ont) {
            DB::transaction(function () use ($customer, $validated) {
                $customer->update([
                    'status' => 'active',
                    'activation_date' => $validated['activation_date'],
                    'notes' => $validated['notes'] ? $customer->notes . "\n[Aktivasi]: " . $validated['notes'] : $customer->notes,
                ]);

                $customer->ont->update([
                    'pppoe_user' => $validated['pppoe_user'],
                    'pppoe_password' => $validated['pppoe_password'],
                    'vlan_mode' => $validated['vlan_mode'],
                    'vlan_id' => $validated['vlan_id'],
                ]);
            });

            return redirect()->route('customers.installed')->with('success', 'Pelanggan berhasil diaktivasi!');
        }
        return redirect()->back()->with('error', 'Pelanggan belum siap diaktivasi.');
    }

    /**
     * Jadwalkan survey
     */
    public function assignSurvey(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'technician_id' => 'required|exists:users,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        $validated['customer_id'] = $customer->id;
        $validated['type'] = 'survey';
        $validated['status'] = 'scheduled';

        TechnicianSchedule::create($validated);
        
        $customer->update(['status' => 'survey']);

        return redirect()->route('customers.survey')
            ->with('success', 'Jadwal survey berhasil ditugaskan kepada teknisi.');
    }

    /**
     * Reschedule survey (ganti tanggal, waktu, dan/atau petugas)
     */
    public function rescheduleSurvey(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'technician_id' => 'required|exists:users,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        // Find the existing scheduled survey
        $schedule = $customer->technicianSchedules()
            ->where('type', 'survey')
            ->where('status', 'scheduled')
            ->first();

        if ($schedule) {
            $schedule->update([
                'technician_id' => $validated['technician_id'],
                'scheduled_date' => $validated['scheduled_date'],
                'scheduled_time' => $validated['scheduled_time'],
                'notes' => $validated['notes'] ?? $schedule->notes,
            ]);
        } else {
            // If no existing schedule found, create one
            TechnicianSchedule::create([
                'customer_id' => $customer->id,
                'technician_id' => $validated['technician_id'],
                'scheduled_date' => $validated['scheduled_date'],
                'scheduled_time' => $validated['scheduled_time'],
                'type' => 'survey',
                'status' => 'scheduled',
                'notes' => $validated['notes'] ?? null,
            ]);
        }

        return redirect()->route('customers.survey')
            ->with('success', 'Jadwal survey berhasil di-reschedule.');
    }

    /**
     * Jadwalkan pemasangan
     */
    public function assignInstall(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'technician_ids' => 'required|array',
            'technician_ids.*' => 'exists:users,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'ont_models' => 'nullable|array',
            'ont_models.*' => 'nullable|string',
            'material_transaction_ids' => 'nullable|array',
            'material_transaction_ids.*' => 'nullable|string',
            'material_items' => 'nullable|array',
            'material_items.*.name' => 'nullable|string',
            'material_items.*.qty' => 'nullable|numeric',
            'material_items.*.unit' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customNotes = [];
        if (!empty($validated['ont_models'])) {
            $onts = array_filter($validated['ont_models']);
            if (count($onts) > 0) {
                $customNotes[] = "ONT: " . implode(', ', $onts);
            }
        }
        if (!empty($validated['material_transaction_ids'])) {
            $trxs = array_filter($validated['material_transaction_ids']);
            if (count($trxs) > 0) {
                $trxNotes = ["Material diambil dari Surat Jalan / Order: " . implode(', ', $trxs)];
                if (!empty($validated['material_items'])) {
                    foreach ($validated['material_items'] as $mItem) {
                        $trxNotes[] = "Material: " . $mItem['name'] . " (" . $mItem['qty'] . " " . $mItem['unit'] . ")";
                    }
                }
                $customNotes[] = implode("\n", $trxNotes);
            }
        }
        if (!empty($validated['notes'])) {
            $customNotes[] = "Catatan Tambahan: " . $validated['notes'];
        }
        $finalNotes = implode("\n\n", $customNotes);

        foreach ($validated['technician_ids'] as $techId) {
            TechnicianSchedule::create([
                'customer_id' => $customer->id,
                'technician_id' => $techId,
                'scheduled_date' => $validated['scheduled_date'],
                'scheduled_time' => $validated['scheduled_time'],
                'type' => 'installation',
                'status' => 'scheduled',
                'notes' => $finalNotes,
            ]);
        }

        return redirect()->route('customers.installed')
            ->with('success', 'Jadwal pemasangan berhasil ditugaskan kepada teknisi.');
    }

    /**
     * Simpan Hasil Survey
     */
    public function storeSurvey(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'surveyor_id' => 'required|exists:users,id',
            'odp_id' => 'nullable|exists:odps,id',
            'distance_meters' => 'nullable|numeric',
            'port_available' => 'nullable|boolean',
            'feasibility' => 'required|in:feasible,not_feasible',
            'notes' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*.label' => 'required|string',
            'photos.*.file' => 'nullable|image|max:5120',
        ]);

        $photoPaths = [];
        if ($request->has('photos') && is_array($request->photos)) {
            foreach ($request->photos as $photoItem) {
                if (isset($photoItem['file']) && $photoItem['file'] instanceof \Illuminate\Http\UploadedFile) {
                    $path = $photoItem['file']->store('surveys', 'public');
                    $photoPaths[] = [
                        'label' => $photoItem['label'],
                        'path' => $path,
                    ];
                }
            }
        }

        $validated['customer_id'] = $customer->id;
        $validated['survey_date'] = now()->toDateString();
        $validated['photos'] = empty($photoPaths) ? null : $photoPaths;

        Survey::create($validated);
        
        // Update schedule status if any
        $schedule = $customer->technicianSchedules()->where('type', 'survey')->where('status', 'scheduled')->first();
        if ($schedule) {
            $schedule->update(['status' => 'completed']);
        }

        if ($validated['feasibility'] === 'feasible') {
            // Biarkan status tetap survey, agar tombol 'Ready Install' muncul
            return redirect()->route('customers.survey')
                ->with('success', 'Hasil survey berhasil disimpan. Pelanggan kini siap untuk instalasi (Ready Install).');
        } else {
            $customer->update(['status' => 'terminated']);
            return redirect()->route('customers.survey')
                ->with('success', 'Hasil survey (not feasible) berhasil disimpan. Pelanggan dibatalkan.');
        }
    }

    /**
     * Tandai pelanggan siap diinstalasi (dari Ready Install)
     */
    public function markInstalling(Customer $customer): RedirectResponse
    {
        if ($customer->status === 'survey') {
            $customer->update(['status' => 'installing']);
        }
        
        return redirect()->to('/customers/installed?search=' . $customer->customer_code)
            ->with('success', 'Pelanggan berhasil dipindahkan ke tahap Instalasi.');
    }
    /**
     * Minta Jadwal Survey (dari Data Booking)
     */
    public function requestSurvey(Customer $customer): RedirectResponse
    {
        if ($customer->status === 'booking') {
            $customer->update(['status' => 'survey']);
        }
        
        return redirect()->route('customers.booking')
            ->with('success', 'Permintaan jadwal survey berhasil dikirim.');
    }
}
