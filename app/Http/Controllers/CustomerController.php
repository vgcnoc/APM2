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
            ->when(auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_all_view_all'), function($q) {
                if (auth()->user()->can('customers_all_view_area')) {
                    $q->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
                } else {
                    $q->where(function($sub) {
                        $sub->where('sales_id', auth()->id())
                            ->orWhereHas('technicianSchedules', function($sq) {
                                $sq->where('technician_id', auth()->id());
                            })
                            ->orWhereHas('surveys', function($sq) {
                                $sq->where('surveyor_id', auth()->id());
                            });
                    });
                }
            })
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
            ->when(auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_booking_view_all'), function($q) {
                if (auth()->user()->can('customers_booking_view_area')) {
                    $q->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
                } else {
                    $q->where('sales_id', auth()->id());
                }
            })
            ->when($request->date, function ($q, $date) {
                $q->whereDate('created_at', $date);
            })
            ->when($request->area, function ($q, $area) {
                $q->where(function($sub) use ($area) {
                    $sub->whereHas('areaModel', function($q2) use ($area) {
                        $q2->where('name', $area);
                    })->orWhere('area', $area);
                });
            })
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->search($request->search);

        $customers = (clone $query)
            ->with(['package', 'sales'])
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
            ->when(auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_survey_view_all'), function($q) {
                if (auth()->user()->hasRole('teknisi')) {
                    $q->where(function($sub) {
                        $sub->whereHas('technicianSchedules', function($sq) {
                            $sq->where('technician_id', auth()->id())->where('type', 'survey');
                        })
                        ->orWhereHas('surveys', function($sq) {
                            $sq->where('surveyor_id', auth()->id());
                        });
                    });
                } else {
                    if (auth()->user()->can('customers_survey_view_area')) {
                        $q->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
                    } else {
                        $q->where(function($sub) {
                            $sub->where('sales_id', auth()->id())
                                ->orWhereHas('technicianSchedules', function($sq) {
                                    $sq->where('technician_id', auth()->id())->where('type', 'survey');
                                })
                                ->orWhereHas('surveys', function($sq) {
                                    $sq->where('surveyor_id', auth()->id());
                                });
                        });
                    }
                }
            })
            ->when($request->technician_id, function ($q, $techId) {
                $q->where(function ($sub) use ($techId) {
                    $sub->whereHas('technicianSchedules', function ($sq) use ($techId) {
                        $sq->where('technician_id', $techId)->where('type', 'survey');
                    })->orWhereHas('surveys', function ($sq) use ($techId) {
                        $sq->where('surveyor_id', $techId);
                    });
                });
            })
            ->when($request->date, function ($q, $date) {
                $q->whereDate('created_at', $date);
            })
            ->when($request->area, function ($q, $area) {
                $q->where(function($sub) use ($area) {
                    $sub->whereHas('areaModel', function($q2) use ($area) {
                        $q2->where('name', $area);
                    })->orWhere('area', $area);
                });
            })
            ->search($request->search);

        // Calculate statistics based on the base query
        $stats = [
            'total' => (clone $baseQuery)->count(),
            'jadwalkan' => (clone $baseQuery)->where('status', 'survey')->doesntHave('surveys')->whereDoesntHave('technicianSchedules', fn($q) => $q->where('type', 'survey'))->count(),
            'laporan' => (clone $baseQuery)->where('status', 'survey')->doesntHave('surveys')->whereHas('technicianSchedules', fn($q) => $q->where('type', 'survey'))->count(),
            'ready' => (clone $baseQuery)->where('status', 'survey')->whereHas('surveys', function ($sq) {
                $sq->where('feasibility', 'feasible');
            })->count(),
            'unfeasible' => (clone $baseQuery)->whereHas('surveys', function ($sq) {
                $sq->where('feasibility', 'not_feasible');
            })->count(),
        ];

        $customers = (clone $baseQuery)
            ->when($tab === 'jadwalkan', function ($q) {
                $q->where('status', 'survey')->doesntHave('surveys')->whereDoesntHave('technicianSchedules', fn($sq) => $sq->where('type', 'survey'));
            })
            ->when($tab === 'laporan', function ($q) {
                $q->where('status', 'survey')->doesntHave('surveys')->whereHas('technicianSchedules', fn($sq) => $sq->where('type', 'survey'));
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
            
        // Ambil semua user yang memiliki hak akses untuk mengisi laporan survey/instalasi
        $technicians = User::permission(['customers_survey_report', 'customers_installed_report'])->where('is_active', true)->get();

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
     * Halaman Instalasi (Jadwal, Lapor, Audit)
     */
    public function installed(Request $request): Response
    {
        $baseQuery = Customer::whereIn('status', ['installing', 'active']);
        
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_installed_view_all')) {
            if (auth()->user()->hasRole('teknisi')) {
                $baseQuery->whereHas('technicianSchedules', function ($sq) {
                    $sq->where('technician_id', auth()->id())->where('type', 'installation');
                });
            } else {
                if (auth()->user()->can('customers_installed_view_area')) {
                    $baseQuery->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
                } else {
                    $baseQuery->where(function($q) {
                        $q->where('sales_id', auth()->id())
                          ->orWhereHas('technicianSchedules', function ($sq) {
                              $sq->where('technician_id', auth()->id())->where('type', 'installation');
                          });
                    });
                }
            }
        }
        
        $stats = [
            'semua' => (clone $baseQuery)->count(),
            'jadwal_pasang' => (clone $baseQuery)
                ->where('status', 'installing')
                ->whereDoesntHave('technicianSchedules', fn($q) => $q->where('type', 'installation'))->count(),
            'laporan_pasang' => (clone $baseQuery)
                ->where('status', 'installing')
                ->whereHas('technicianSchedules', function($q) {
                    $q->where('type', 'installation')->where('status', 'scheduled');
                })->count(),
            'audit' => (clone $baseQuery)
                ->where('status', 'installing')
                ->where('is_audited', false)
                ->whereDoesntHave('technicianSchedules', function($q) {
                    $q->where('type', 'installation')->where('status', 'scheduled');
                })
                ->whereHas('ont', function ($q) {
                    $q->whereNotNull('rx_power');
                })->count(),
            'selesai_instalasi' => (clone $baseQuery)
                ->where(function($q) {
                    $q->where('status', 'installing')->where('is_audited', true)
                      ->orWhere('status', 'active');
                })
                ->count(),
        ];

        $customerQuery = clone $baseQuery;
        
        $currentTab = $request->tab;
        
        if ($currentTab) {
            if ($currentTab === 'jadwal_pasang') {
                $customerQuery->where('status', 'installing')->whereDoesntHave('technicianSchedules', fn($q) => $q->where('type', 'installation'));
            } elseif ($currentTab === 'laporan_pasang') {
                $customerQuery->where('status', 'installing')->whereHas('technicianSchedules', function($q) {
                    $q->where('type', 'installation')->where('status', 'scheduled');
                });
            } elseif ($currentTab === 'audit') {
                $customerQuery->where('status', 'installing')->where('is_audited', false)->whereDoesntHave('technicianSchedules', function($q) {
                    $q->where('type', 'installation')->where('status', 'scheduled');
                })->whereHas('ont', function ($q) {
                        $q->whereNotNull('rx_power');
                    });
            } elseif ($currentTab === 'selesai_instalasi') {
                $customerQuery->where(function($q) {
                    $q->where('status', 'installing')->where('is_audited', true)
                      ->orWhere('status', 'active');
                });
            }
        }

        $customers = $customerQuery
            ->when($request->date_from, function($q, $dateFrom) {
                $q->whereHas('technicianSchedules', function($sub) use ($dateFrom) {
                    $sub->where('scheduled_date', '>=', $dateFrom)->where('type', 'installation');
                });
            })
            ->when($request->date_to, function($q, $dateTo) {
                $q->whereHas('technicianSchedules', function($sub) use ($dateTo) {
                    $sub->where('scheduled_date', '<=', $dateTo)->where('type', 'installation');
                });
            })
            ->when($request->area, function($q, $area) {
                $q->where(function($sub) use ($area) {
                    $sub->whereHas('areaModel', function($q2) use ($area) {
                        $q2->where('name', $area);
                    })->orWhere('area', $area);
                });
            })
            ->with(['package', 'surveys.odp', 'ont.odp.odc.olt', 'sales', 'technicianSchedules' => function ($q) {
                $q->where('type', 'installation')->with('technician');
            }])
            ->search($request->search)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        // Ambil semua user yang memiliki hak akses untuk mengisi laporan
        $technicians = User::permission(['customers_survey_report', 'customers_installed_report'])->where('is_active', true)->get();
        $availableOnts = \App\Models\Ont::whereIn('status', ['Sudah Set', 'Belum Set/Baru Input', 'inactive'])
            ->whereNull('customer_id')
            ->get();
            
        $materialTransactions = \App\Models\MaterialTransaction::where('type', 'out')
            ->with('items.material.stocks')
            ->latest()
            ->limit(100)
            ->get();

        $areas = \App\Models\Area::orderBy('name')->pluck('name')->toArray();

        return Inertia::render('Customers/Installed', [
            'customers' => $customers,
            'technicians' => $technicians,
            'availableOnts' => $availableOnts,
            'materialTransactions' => $materialTransactions,
            'packages' => \App\Models\InternetPackage::active()->get(),
            'areas' => $areas,
            'stats' => $stats,
            'filters' => $request->only(['search', 'tab', 'date_from', 'date_to', 'area']),
        ]);
    }

    /**
     * Halaman Aktivasi
     */
    public function activation(Request $request): Response
    {
        $baseQuery = Customer::installed()->where('status', 'installing')->where('is_audited', true);
        
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_activation_view_all')) {
            if (auth()->user()->can('customers_activation_view_area')) {
                $baseQuery->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
            } else {
                $baseQuery->where(function($q) {
                    $q->where('sales_id', auth()->id())
                      ->orWhereHas('technicianSchedules', function ($sq) {
                          $sq->where('technician_id', auth()->id())->where('type', 'installation');
                      });
                });
            }
        }
        
        $stats = [
            'aktivasi' => (clone $baseQuery)->count(),
        ];

        $customers = (clone $baseQuery)
            ->with(['package', 'surveys.odp', 'ont.odp.odc.olt', 'technicianSchedules' => function ($q) {
                $q->where('type', 'installation')->with('technician');
            }])
            ->search($request->search)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Activation', [
            'customers' => $customers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'tab', 'date', 'area']),
        ]);
    }

    /**
     * Halaman Pelanggan Aktif
     */
    public function active(Request $request): Response
    {
        $baseQuery = Customer::where('status', 'active');
        
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_active_view_all')) {
            if (auth()->user()->can('customers_active_view_area')) {
                $baseQuery->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
            } else {
                $baseQuery->where(function($q) {
                    $q->where('sales_id', auth()->id())
                      ->orWhereHas('technicianSchedules', function ($sq) {
                          $sq->where('technician_id', auth()->id());
                      });
                });
            }
        }
        
        $stats = [
            'aktif' => (clone $baseQuery)->count(),
        ];

        $customers = (clone $baseQuery)
            ->with(['package', 'surveys.odp', 'ont.odp.odc.olt', 'technicianSchedules' => function ($q) {
                $q->where('type', 'installation')->with('technician');
            }])
            ->search($request->search)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Active', [
            'customers' => $customers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'tab', 'date', 'area']),
        ]);
    }

    /**
     * Form tambah pelanggan baru (booking)
     */
    public function create(): Response
    {
        $areas = \App\Models\Area::pluck('name');
        
        $sales = User::permission('customers_booking_create')->where('is_active', true)->get();
        if (!auth()->user()->hasRole('admin')) {
            $currentUser = auth()->user();
            if (!$sales->contains('id', $currentUser->id)) {
                $sales->push($currentUser);
            }
        }

        return Inertia::render('Customers/Create', [
            'packages' => InternetPackage::active()->get(),
            'sales' => $sales,
            'areas' => $areas,
        ]);
    }

    /**
     * Simpan pelanggan baru
     */
    public function store(Request $request): RedirectResponse
    {
        if (!auth()->user()->can('customers_booking_create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat booking.');
        }

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

        if (!auth()->user()->hasRole('admin')) {
            $validated['sales_id'] = auth()->id();
        }

        if ($request->hasFile('identity_photo')) {
            $validated['identity_photo'] = $request->file('identity_photo')->store('ktp', 'public');
        }

        // Auto-create new area if not exists in master data
        if (!empty($validated['area'])) {
            $areaModel = \App\Models\Area::firstOrCreate(['name' => $validated['area']]);
            $validated['area_id'] = $areaModel->id;
        }

        $validated['status'] = 'booking'; // Ensure it goes to booking

        $customer = Customer::create($validated);

        \App\Models\AuditLog::createLog('Create Booking', $customer, null, 'booking', 'Membuat booking baru');

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
            'ont.odp.odc.pon',
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
            'source' => request()->query('source'),
            'availableOdps' => Odp::active()->with(['odc.olt', 'onts.customer'])->get(),
            'availableOnts' => Ont::where('status', 'Sudah Set')->whereNull('customer_id')->get(),
        ]);
    }

    /**
     * Form edit pelanggan
     */
    public function edit(Customer $customer): Response
    {
        $customer->load(['ont.odp', 'package']);
        
        $areas = \App\Models\Area::pluck('name');

        $sales = User::permission('customers_booking_create')->where('is_active', true)->get();
        if (!auth()->user()->hasRole('admin')) {
            $currentUser = auth()->user();
            if (!$sales->contains('id', $currentUser->id)) {
                $sales->push($currentUser);
            }
            if ($customer->sales_id && !$sales->contains('id', $customer->sales_id)) {
                $sales->push(User::find($customer->sales_id));
            }
        }

        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
            'packages' => InternetPackage::active()->get(),
            'availableOdps' => Odp::active()->with('odc.olt')->get(),
            'sales' => $sales,
            'areas' => $areas,
        ]);
    }

    /**
     * Update data pelanggan
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'area' => 'nullable|string',
            'identity_photo' => 'nullable|image|max:5120',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'package_id' => 'nullable|exists:internet_packages,id',
            'status' => ['required', Rule::in(['booking', 'survey', 'installing', 'active', 'suspended', 'terminated'])],
            'notes' => 'nullable|string',
            'base_amount' => 'nullable|numeric|min:0',
            'registration_date' => 'nullable|date',
            'installation_fee' => 'nullable|numeric|min:0',
            'sales_id' => 'nullable|exists:users,id',
        ]);

        if (!auth()->user()->hasRole('admin')) {
            unset($validated['sales_id']);
        }

        if ($request->hasFile('identity_photo')) {
            $validated['identity_photo'] = $request->file('identity_photo')->store('ktp', 'public');
        }

        // Auto-create new area if not exists in master data
        if (!empty($validated['area'])) {
            $areaModel = \App\Models\Area::firstOrCreate(['name' => $validated['area']]);
            $validated['area_id'] = $areaModel->id;
        }

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
        // Authorization check if using spatie roles
        if (auth()->check() && !auth()->user()->can('customers_delete') && !auth()->user()->hasRole('admin')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus data pelanggan.');
        }

        DB::transaction(function () use ($customer) {
            // Lepaskan ONT jika ada
            if ($customer->ont) {
                $odp = $customer->ont->odp;
                $portNumber = $customer->ont->port_number;
                
                // Update ODP Port status back to available
                if ($odp && $portNumber) {
                    \App\Models\OdpPort::where('odp_id', $odp->id)
                        ->where('port_number', $portNumber)
                        ->update(['status' => 'available']);
                }

                $customer->ont->update([
                    'customer_id' => null, 
                    'status' => 'inactive',
                    'odp_id' => null,
                    'port_number' => null,
                    'odp_port_id' => null
                ]);

                // Kurangi used_ports di ODP
                if ($odp) {
                    if ($odp->used_ports > 0) {
                        $odp->decrement('used_ports');
                        
                        // Cek dan update status ODP jika sudah tidak penuh
                        if ($odp->used_ports - 1 < $odp->total_ports && $odp->status === 'full') {
                            $odp->update(['status' => 'active']);
                        }
                    }
                }
            }

            // Hapus data terkait
            $customer->technicianSchedules()->delete();
            $customer->surveys()->delete();

            $customer->delete();
        });

        return redirect()->back()
            ->with('success', 'Data pelanggan berhasil dihapus secara permanen.');
    }

    /**
     * Assign ONT ke pelanggan (integrasi Customer ↔ ONT ↔ ODP)
     */
    public function assignOnt(Request $request, Customer $customer): RedirectResponse
    {
        if (!auth()->user()->can('customers_installed_report') && !auth()->user()->can('customers_installed_edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengisi/mengedit laporan instalasi.');
        }

        $ont = Ont::where('customer_id', $customer->id)->first();
        $isEdit = $ont && $ont->start_time; // If start_time exists, it's already installed, so it's an edit

        // Ensure times only have H:i format if they come with seconds
        if ($request->has('start_time') && strlen($request->start_time) > 5) {
            $request->merge(['start_time' => substr($request->start_time, 0, 5)]);
        }
        if ($request->has('end_time') && strlen($request->end_time) > 5) {
            $request->merge(['end_time' => substr($request->end_time, 0, 5)]);
        }

        $rules = [
            'odp_id' => 'required|exists:odps,id',
            'port_number' => 'required|integer|min:1',
            'rx_power' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
        ];

        // Photos are required for new installation, nullable for edits
        $rules['photo_odp'] = ($isEdit && $ont->photo_odp) ? 'nullable|image|max:5120' : 'required|image|max:5120';
        $rules['photo_installation'] = ($isEdit && $ont->photo_installation) ? 'nullable|image|max:5120' : 'required|image|max:5120';
        $rules['photo_ont'] = ($isEdit && $ont->photo_ont) ? 'nullable|image|max:5120' : 'required|image|max:5120';
        $rules['photo_customer'] = ($isEdit && $ont->photo_customer) ? 'nullable|image|max:5120' : 'required|image|max:5120';
        $rules['photo_redaman'] = ($isEdit && $ont->photo_redaman) ? 'nullable|image|max:5120' : 'required|image|max:5120';

        $validated = $request->validate($rules);

        if ($request->hasFile('photo_odp')) $validated['photo_odp'] = $request->file('photo_odp')->store('installations', 'public');
        if ($request->hasFile('photo_installation')) $validated['photo_installation'] = $request->file('photo_installation')->store('installations', 'public');
        if ($request->hasFile('photo_ont')) $validated['photo_ont'] = $request->file('photo_ont')->store('installations', 'public');
        if ($request->hasFile('photo_customer')) $validated['photo_customer'] = $request->file('photo_customer')->store('installations', 'public');
        if ($request->hasFile('photo_redaman')) $validated['photo_redaman'] = $request->file('photo_redaman')->store('installations', 'public');

        $odp = Odp::findOrFail($validated['odp_id']);
        if ($odp->area_id !== $customer->area_id) {
            return back()->withErrors(['odp_id' => 'ODP tidak sesuai dengan Area/Wilayah pelanggan.']);
        }

        if ($validated['end_time'] < $validated['start_time']) {
            return back()->withErrors(['end_time' => 'Jam selesai tidak boleh lebih awal dari jam mulai.']);
        }

        DB::transaction(function () use ($validated, $customer, $odp, $request, $ont) {
            $isNewAssignment = false;
            
            // Update existing linked ONT or Create new
            if ($ont) {
                // Free old port if exist and changed
                if ($ont->odp_id && $ont->port_number && ($ont->odp_id != $validated['odp_id'] || $ont->port_number != $validated['port_number'])) {
                    \App\Models\OdpPort::where('odp_id', $ont->odp_id)
                        ->where('port_number', $ont->port_number)
                        ->update(['status' => 'available']);
                    
                    $oldOdp = \App\Models\Odp::find($ont->odp_id);
                    if ($oldOdp && $oldOdp->used_ports > 0) {
                        $oldOdp->decrement('used_ports');
                        if ($oldOdp->used_ports - 1 < $oldOdp->total_ports && $oldOdp->status === 'full') {
                            $oldOdp->update(['status' => 'active']);
                        }
                    }
                    $isNewAssignment = true;
                } elseif (!$ont->odp_id || !$ont->port_number) {
                    $isNewAssignment = true;
                }

                // Update existing linked ONT
                $ont->update([
                    'odp_id' => $validated['odp_id'],
                    'port_number' => $validated['port_number'],
                    'rx_power' => $validated['rx_power'] ?? null,
                    'start_time' => $validated['start_time'] ?? null,
                    'end_time' => $validated['end_time'] ?? null,
                    'photo_odp' => $validated['photo_odp'] ?? $ont->photo_odp,
                    'photo_installation' => $validated['photo_installation'] ?? $ont->photo_installation,
                    'photo_ont' => $validated['photo_ont'] ?? $ont->photo_ont,
                    'photo_customer' => $validated['photo_customer'] ?? $ont->photo_customer,
                    'photo_redaman' => $validated['photo_redaman'] ?? $ont->photo_redaman,
                    'status' => 'active',
                ]);
            } else {
                $isNewAssignment = true;
                // Create new ONT if none is linked
                $ont = Ont::create([
                    'odp_id' => $validated['odp_id'],
                    'customer_id' => $customer->id,
                    'serial_number' => 'SN-' . strtoupper(\Illuminate\Support\Str::random(8)),
                    'port_number' => $validated['port_number'],
                    'rx_power' => $validated['rx_power'] ?? null,
                    'start_time' => $validated['start_time'] ?? null,
                    'end_time' => $validated['end_time'] ?? null,
                    'photo_odp' => $validated['photo_odp'] ?? null,
                    'photo_installation' => $validated['photo_installation'] ?? null,
                    'photo_ont' => $validated['photo_ont'] ?? null,
                    'photo_customer' => $validated['photo_customer'] ?? null,
                    'photo_redaman' => $validated['photo_redaman'] ?? null,
                    'status' => 'active',
                ]);
            }

            // Update used_ports di ODP baru ONLY if it is a new assignment or port changed
            if ($isNewAssignment) {
                $newOdp = Odp::find($validated['odp_id']);
                $newOdp->increment('used_ports');
            }

            // Update odp_ports if exists
            $newOdpPort = \App\Models\OdpPort::where('odp_id', $newOdp->id)
                ->where('port_number', $validated['port_number'])
                ->first();
            if ($newOdpPort) {
                $newOdpPort->update([
                    'status' => 'used'
                ]);
            }

            // Jika ODP penuh, update statusnya
            if ($newOdp->used_ports >= $newOdp->total_ports) {
                $newOdp->update(['status' => 'full']);
            }

            // Update status jadwal teknisi ke done jika ada
            $schedules = $customer->technicianSchedules()->where('type', 'installation')->where('status', 'scheduled')->get();
            foreach ($schedules as $schedule) {
                $schedule->update(['status' => 'done']);
            }
        });

        $ont = Ont::where('customer_id', $customer->id)->first();
        \App\Models\AuditLog::createLog('Laporan Instalasi', $customer, 'installing', 'installing', 'Mengisi laporan instalasi ONT ' . ($ont ? $ont->serial_number : ''));

        return back()
            ->with('success', 'Laporan instalasi berhasil disimpan. Silakan lanjutkan dengan Audit.');
    }

    /**
     * Selesaikan audit instalasi
     */
    public function audit(Request $request, Customer $customer): RedirectResponse
    {
        if (!auth()->user()->can('customers_installed_audit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melakukan audit.');
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        if ($customer->status === 'installing' && !$customer->is_audited && $customer->ont) {
            $customer->update([
                'is_audited' => true,
                'notes' => $validated['notes'] ? $customer->notes . "\n[Audit]: " . $validated['notes'] : $customer->notes,
            ]);
        }

        \App\Models\AuditLog::createLog('Audit Instalasi', $customer, 'installing', 'installing', 'Melakukan audit instalasi: ' . ($validated['notes'] ?? 'Disetujui'));

        return redirect()->back()
            ->with('success', 'Audit instalasi selesai. Pelanggan kini siap diaktivasi.');
    }

    /**
     * Aktivasi pelanggan setelah audit selesai
     */
    public function activate(Request $request, Customer $customer): RedirectResponse
    {
        if (!auth()->user()->can('customers_activation_activate')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melakukan aktivasi.');
        }

        if ($customer->status !== 'installing' || !$customer->is_audited) {
            return redirect()->back()->withErrors(['error' => 'Pelanggan belum memenuhi syarat untuk aktivasi.']);
        }

        $validated = $request->validate([
            'activation_date' => 'required|date',
            'pppoe_user' => 'nullable|string',
            'pppoe_password' => 'nullable|string',
            'vlan_mode' => 'nullable|string',
            'vlan_id' => 'nullable|string',
            'access_mode' => 'nullable|string',
            'ip_login' => 'nullable|string',
            'login_user' => 'nullable|string',
            'login_password' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($customer->status === 'installing' && $customer->is_audited && $customer->ont) {
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
                    'access_mode' => $validated['access_mode'],
                    'ip_login' => $validated['ip_login'],
                    'login_user' => $validated['login_user'],
                    'login_password' => $validated['login_password'],
                ]);
            });

            \App\Models\AuditLog::createLog('Aktivasi Layanan', $customer, 'installing', 'active', 'Mengaktifkan layanan pelanggan');

            return redirect()->route('customers.installed')->with('success', 'Pelanggan berhasil diaktivasi!');
        }
        return redirect()->back()->with('error', 'Pelanggan belum siap diaktivasi.');
    }

    public function updateOntInline(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'pppoe_user' => 'nullable|string',
            'pppoe_password' => 'nullable|string',
            'vlan_mode' => 'nullable|string',
            'vlan_id' => 'nullable|string',
            'access_mode' => 'nullable|string',
            'ip_login' => 'nullable|string',
            'login_user' => 'nullable|string',
            'login_password' => 'nullable|string',
        ]);

        if ($customer->ont) {
            $customer->ont->update($validated);
            return back()->with('success', 'Data konfigurasi ONT berhasil diperbarui.');
        }
        
        return back()->with('error', 'Data ONT tidak ditemukan.');
    }

    /**
     * Jadwalkan survey
     */
    public function assignSurvey(Request $request, Customer $customer): RedirectResponse
    {
        if (!auth()->user()->can('customers_survey_assign')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menjadwalkan survey.');
        }

        $validated = $request->validate([
            'technician_ids' => 'required|array',
            'technician_ids.*' => 'exists:users,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        foreach ($validated['technician_ids'] as $techId) {
            TechnicianSchedule::create([
                'customer_id' => $customer->id,
                'technician_id' => $techId,
                'type' => 'survey',
                'status' => 'scheduled',
                'scheduled_date' => $validated['scheduled_date'],
                'scheduled_time' => $validated['scheduled_time'],
                'notes' => $validated['notes'],
            ]);
        }
        
        $customer->update(['status' => 'survey']);

        \App\Models\AuditLog::createLog('Jadwal Survey', $customer, 'booking', 'survey', 'Menjadwalkan survey');

        return redirect()->route('customers.survey')
            ->with('success', 'Jadwal survey berhasil ditugaskan kepada teknisi.');
    }

    /**
     * Reschedule survey (ganti tanggal, waktu, dan/atau petugas)
     */
    public function rescheduleSurvey(Request $request, Customer $customer): RedirectResponse
    {
        if (!auth()->user()->can('customers_survey_assign')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah jadwal survey.');
        }

        $validated = $request->validate([
            'technician_ids' => 'required|array',
            'technician_ids.*' => 'exists:users,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string',
        ]);

        // Find existing notes from old schedule if any
        $oldSchedule = $customer->technicianSchedules()
            ->where('type', 'survey')
            ->where('status', 'scheduled')
            ->first();
        $notes = $validated['notes'] ?? ($oldSchedule ? $oldSchedule->notes : null);

        // Hapus jadwal survey sebelumnya
        $customer->technicianSchedules()
            ->where('type', 'survey')
            ->where('status', 'scheduled')
            ->delete();

        foreach ($validated['technician_ids'] as $techId) {
            TechnicianSchedule::create([
                'customer_id' => $customer->id,
                'technician_id' => $techId,
                'type' => 'survey',
                'status' => 'scheduled',
                'scheduled_date' => $validated['scheduled_date'],
                'scheduled_time' => $validated['scheduled_time'],
                'notes' => $notes,
            ]);
        }

        \App\Models\AuditLog::createLog('Reschedule Survey', $customer, 'survey', 'survey', 'Menjadwalkan ulang survey');

        return redirect()->route('customers.survey')
            ->with('success', 'Jadwal survey berhasil di-reschedule.');
    }

    /**
     * Jadwalkan pemasangan
     */
    public function assignInstall(Request $request, Customer $customer): RedirectResponse
    {
        if (!auth()->user()->can('customers_installed_assign')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menjadwalkan instalasi.');
        }

        \Log::info('assignInstall called', ['customer_id' => $customer->id, 'data' => $request->all()]);

        // Normalize scheduled_time - remove seconds if browser sends H:i:s
        $time = $request->input('scheduled_time');
        if ($time && preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            $request->merge(['scheduled_time' => substr($time, 0, 5)]);
        }

        // Normalize ont_models - cast integer IDs to strings for validation
        if ($request->has('ont_models')) {
            $request->merge([
                'ont_models' => array_map(function ($v) {
                    return $v !== null && $v !== '' ? (string) $v : $v;
                }, $request->input('ont_models', []))
            ]);
        }

        $validated = $request->validate([
            'technician_ids' => 'required|array|min:1',
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

        \Log::info('assignInstall validated OK', ['customer_id' => $customer->id]);

        $customNotes = [];
        if (!empty($validated['ont_models'])) {
            $ontIds = array_filter($validated['ont_models']);
            if (count($ontIds) > 0) {
                $onts = Ont::whereIn('id', $ontIds)->get();
                
                // VALIDASI AREA ONT
                foreach ($onts as $ont) {
                    if ($ont->area_id !== $customer->area_id) {
                        return redirect()->back()->withErrors(['ont_models' => 'ONT ' . $ont->serial_number . ' tidak sesuai dengan Area/Wilayah pelanggan.'])->with('error', 'Data ONT/Material tidak sesuai dengan Area/Wilayah pelanggan.');
                    }
                }
                
                $ontNames = [];
                
                // Update customer_id in onts table
                DB::transaction(function () use ($onts, $customer) {
                    foreach ($onts as $ont) {
                        $ont->update([
                            'customer_id' => $customer->id,
                            'rx_power' => null,
                            'start_time' => null,
                            'end_time' => null,
                            'photo_odp' => null,
                            'photo_installation' => null,
                            'photo_ont' => null,
                            'photo_customer' => null,
                            'photo_redaman' => null,
                        ]);
                    }
                });

                foreach ($onts as $ont) {
                    $name = $ont->brand;
                    if ($ont->model) $name .= ' ' . $ont->model;
                    $name .= ' (SN: ' . $ont->serial_number . ')';
                    $ontNames[] = $name;
                }
                
                if (count($ontNames) > 0) {
                    $customNotes[] = "ONT: " . implode(', ', $ontNames);
                }
            }
        }
        if (!empty($validated['material_transaction_ids'])) {
            $trxs = array_filter($validated['material_transaction_ids']);
            if (count($trxs) > 0) {
                $transactions = \App\Models\MaterialTransaction::whereIn('transaction_number', $trxs)->get();
                // VALIDASI AREA MATERIAL TRANSACTION
                foreach ($transactions as $trx) {
                    if ($trx->area_id !== $customer->area_id) {
                        return redirect()->back()->withErrors(['material_transaction_ids' => 'Material/Surat Jalan ' . $trx->transaction_number . ' tidak sesuai dengan Area/Wilayah pelanggan.'])->with('error', 'Data ONT/Material tidak sesuai dengan Area/Wilayah pelanggan.');
                    }
                }

                $trxNotes = ["Material diambil dari Surat Jalan / Order: " . implode(', ', $trxs)];
                if (!empty($validated['material_items'])) {
                    foreach ($validated['material_items'] as $mItem) {
                        $trxNotes[] = "Material: " . $mItem['name'] . " (" . $mItem['qty'] . " " . $mItem['unit'] . ")";
                        
                        // Deduct Area Stock
                        if (isset($mItem['id']) && is_numeric($mItem['id'])) {
                            $transactionItem = \App\Models\MaterialTransactionItem::find($mItem['id']);
                            if ($transactionItem && $transactionItem->material_id) {
                                $materialStock = \App\Models\MaterialStock::where('material_id', $transactionItem->material_id)
                                    ->where('area_id', $customer->area_id)
                                    ->first();
                                
                                if ($materialStock) {
                                    $qty = floatval($mItem['qty']);
                                    $material = \App\Models\Material::find($transactionItem->material_id);
                                    if ($material) {
                                        $unitLower = strtolower($mItem['unit']);
                                        if (in_array($material->category, ['Kabel Drop', 'Kabel Drop / Frecon', 'Kabel Frecon', 'Kabel'])) {
                                            if ($unitLower === 'roll' || $unitLower === 'rol' || $unitLower === 'pcs') {
                                                $mpr = floatval($material->meter_per_roll) > 0 ? floatval($material->meter_per_roll) : 1000;
                                                $qty = $qty * $mpr;
                                            }
                                        } else if ($material->category === 'Paku Klem') {
                                            if ($unitLower === 'pack' || $unitLower === 'bungkus') {
                                                $ppp = floatval($material->pcs_per_pack) > 0 ? floatval($material->pcs_per_pack) : 1;
                                                $qty = $qty * $ppp;
                                            }
                                        } else if ($material->category === 'Isolasi') {
                                            if ($unitLower === 'pcs' || $unitLower === 'pcs (utuh)') {
                                                $cpp = floatval($material->cm_per_pcs) > 0 ? floatval($material->cm_per_pcs) : 50;
                                                $qty = $qty * $cpp;
                                            }
                                        }
                                    }
                                    $materialStock->stock -= $qty;
                                    $materialStock->save();
                                }
                            }
                        }
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

        \App\Models\AuditLog::createLog('Jadwal Instalasi', $customer, 'installing', 'installing', 'Menjadwalkan pemasangan');

        return redirect()->route('customers.installed')
            ->with('success', 'Jadwal pemasangan berhasil ditugaskan kepada teknisi.');
    }

    /**
     * Simpan Hasil Survey
     */
    public function storeSurvey(Request $request, Customer $customer): RedirectResponse
    {
        if (!auth()->user()->can('customers_survey_report')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengisi laporan survey.');
        }

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

        if (!empty($validated['odp_id'])) {
            $odp = Odp::findOrFail($validated['odp_id']);
            if ($odp->area_id !== $customer->area_id) {
                return back()->withErrors(['odp_id' => 'ODP tidak sesuai dengan Area/Wilayah pelanggan.']);
            }

            // Cek ketersediaan port ODP termasuk antrean instalasi (pelanggan yang sudah disurvey tapi belum diinstal)
            $pendingCount = \App\Models\Customer::whereIn('status', ['survey', 'installing'])
                ->where('id', '!=', $customer->id) // kecualikan pelanggan ini sendiri
                ->whereHas('surveys', function($q) use ($odp) {
                    $q->where('odp_id', $odp->id);
                })
                ->whereDoesntHave('ont', function($q) use ($odp) {
                    $q->where('odp_id', $odp->id);
                })->count();

            $availablePorts = $odp->total_ports - $odp->used_ports - $pendingCount;

            if ($availablePorts <= 0) {
                return back()->withErrors(['odp_id' => 'ODP ini sudah penuh (termasuk antrean pelanggan yang belum diinstalasi). Silakan pilih ODP lain.']);
            }
        }

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
            $schedule->update(['status' => 'done']);
        }

        if ($validated['feasibility'] === 'feasible') {
            \App\Models\AuditLog::createLog('Laporan Survey', $customer, 'survey', 'survey', 'Mengisi laporan survey (feasible)');
            // Biarkan status tetap survey, agar tombol 'Ready Install' muncul
            return redirect()->route('customers.survey')
                ->with('success', 'Hasil survey berhasil disimpan. Pelanggan kini siap untuk instalasi (Ready Install).');
        } else {
            $customer->update(['status' => 'terminated']);
            \App\Models\AuditLog::createLog('Laporan Survey', $customer, 'survey', 'terminated', 'Mengisi laporan survey (not feasible)');
            return redirect()->route('customers.survey')
                ->with('success', 'Hasil survey (not feasible) berhasil disimpan. Pelanggan dibatalkan.');
        }
    }

    /**
     * Tandai pelanggan siap diinstalasi (dari Ready Install)
     */
    public function markInstalling(Customer $customer): RedirectResponse
    {
        if (!auth()->user()->can('customers_survey_mark_ready')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah status ke Ready Instalasi.');
        }

        if ($customer->status === 'survey') {
            $customer->update(['status' => 'installing']);
            \App\Models\AuditLog::createLog('Ready Instalasi', $customer, 'survey', 'installing', 'Memindahkan pelanggan ke tahap Ready Instalasi');
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

    public function bulkDestroy(Request $request): RedirectResponse
    {
        if (!auth()->user()->can('customers_delete') && !auth()->user()->hasRole('admin')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus data pelanggan.');
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:customers,id'
        ]);

        $count = 0;
        DB::transaction(function () use ($request, &$count) {
            $customers = Customer::whereIn('id', $request->ids)->get();
            foreach ($customers as $customer) {
                // Lepaskan ONT jika ada
                if ($customer->ont) {
                    $odp = $customer->ont->odp;
                    $portNumber = $customer->ont->port_number;

                    // Update ODP Port status back to available
                    if ($odp && $portNumber) {
                        \App\Models\OdpPort::where('odp_id', $odp->id)
                            ->where('port_number', $portNumber)
                            ->update(['status' => 'available']);
                    }

                    $customer->ont->update([
                        'customer_id' => null, 
                        'status' => 'inactive',
                        'odp_id' => null,
                        'port_number' => null,
                        'odp_port_id' => null
                    ]);

                    if ($odp && $odp->used_ports > 0) {
                        $odp->decrement('used_ports');
                        if ($odp->used_ports - 1 < $odp->total_ports && $odp->status === 'full') {
                            $odp->update(['status' => 'active']);
                        }
                    }
                }
                
                // Hapus data terkait
                $customer->technicianSchedules()->delete();
                $customer->surveys()->delete();
                $customer->delete();
                
                $count++;
            }
        });

        return redirect()->back()->with('success', "$count data pelanggan berhasil dihapus secara permanen.");
    }
}

