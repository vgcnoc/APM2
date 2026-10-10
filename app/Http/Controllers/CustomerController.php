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
        $baseQuery = Customer::query()
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
            });

        // 1. Calculate Stats
        $now = now();
        $lastMonth = now()->subMonth();

        $totalCustomers = (clone $baseQuery)->count();
        $lastMonthTotal = (clone $baseQuery)->where('created_at', '<', $now->copy()->startOfMonth())->count();
        $totalGrowth = $lastMonthTotal > 0 ? round((($totalCustomers - $lastMonthTotal) / $lastMonthTotal) * 100, 1) : ($totalCustomers > 0 ? 100 : 0);

        $activeCustomers = (clone $baseQuery)->where('status', 'active')->count();
        $lastMonthActive = (clone $baseQuery)->where('status', 'active')->where('created_at', '<', $now->copy()->startOfMonth())->count();
        $activeGrowth = $lastMonthActive > 0 ? round((($activeCustomers - $lastMonthActive) / $lastMonthActive) * 100, 1) : ($activeCustomers > 0 ? 100 : 0);
        $activePercentage = $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 1) : 0;

        $pendingCustomers = (clone $baseQuery)->whereIn('status', ['booking', 'survey', 'installing'])->count();
        $lastMonthPending = (clone $baseQuery)->whereIn('status', ['booking', 'survey', 'installing'])->where('created_at', '<', $now->copy()->startOfMonth())->count();
        $pendingGrowth = $lastMonthPending > 0 ? round((($pendingCustomers - $lastMonthPending) / $lastMonthPending) * 100, 1) : ($pendingCustomers > 0 ? 100 : 0);
        $pendingPercentage = $totalCustomers > 0 ? round(($pendingCustomers / $totalCustomers) * 100, 1) : 0;

        $inactiveCustomers = (clone $baseQuery)->whereIn('status', ['suspended', 'terminated'])->count();
        $lastMonthInactive = (clone $baseQuery)->whereIn('status', ['suspended', 'terminated'])->where('created_at', '<', $now->copy()->startOfMonth())->count();
        $inactiveGrowth = $lastMonthInactive > 0 ? round((($inactiveCustomers - $lastMonthInactive) / $lastMonthInactive) * 100, 1) : ($inactiveCustomers > 0 ? 100 : 0);
        $inactivePercentage = $totalCustomers > 0 ? round(($inactiveCustomers / $totalCustomers) * 100, 1) : 0;

        // 2. Fetch Data
        $customers = (clone $baseQuery)
            ->with(['package', 'ont.odp', 'surveys.odp', 'areaModel', 'user'])
            ->search($request->search)
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->package_id, fn ($q, $pkg) => $q->where('package_id', $pkg))
            ->when($request->area_id, fn ($q, $area) => $q->where('area_id', $area))
            ->when($request->odp_id, fn ($q, $odp) => $q->whereHas('ont', fn ($oq) => $oq->where('odp_id', $odp))->orWhereHas('surveys', fn ($sq) => $sq->where('odp_id', $odp)))
            ->when($request->sort_by, function ($q, $sort) {
                return match($sort) {
                    'terlama' => $q->orderBy('created_at', 'asc'),
                    'nama_asc' => $q->orderBy('name', 'asc'),
                    'nama_desc' => $q->orderBy('name', 'desc'),
                    default => $q->orderByDesc('created_at'),
                };
            }, fn ($q) => $q->orderByDesc('created_at'))
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'packages' => InternetPackage::active()->get(),
            'areas' => \App\Models\Area::orderBy('name')->get(),
            'odps' => class_exists('\App\Models\Odp') ? \App\Models\Odp::orderBy('name')->get() : [],
            'officers' => \App\Models\User::where('is_on_duty', true)->get(['id', 'name', 'phone', 'role']),
            'filters' => $request->only(['search', 'status', 'package_id', 'area_id', 'odp_id', 'sort_by']),
            'stats' => [
                'total' => ['value' => $totalCustomers, 'growth' => $totalGrowth],
                'active' => ['value' => $activeCustomers, 'percentage' => $activePercentage, 'growth' => $activeGrowth],
                'pending' => ['value' => $pendingCustomers, 'percentage' => $pendingPercentage, 'growth' => $pendingGrowth],
                'inactive' => ['value' => $inactiveCustomers, 'percentage' => $inactivePercentage, 'growth' => $inactiveGrowth],
            ],
            'statusOptions' => [
                'booking' => 'Booking',
                'survey' => 'Survey',
                'installing' => 'Proses Pasang',
                'active' => 'Aktif',
                'suspended' => 'Suspended',
                'terminated' => 'Terminated',
            ],
            'onlineUsernames' => (function() {
                try {
                    return \App\Models\Radius\RadAcct::online()->pluck('username')->toArray();
                } catch (\Exception $e) {
                    return [];
                }
            })(),
        ]);
    }

    /**
     * Daftar pelanggan yang sedang online (RADIUS), tanpa voucher.
     */
    public function online(Request $request): Response
    {
        // 1. Ambil semua sesi online dari RADIUS
        try {
            $sessions = \App\Models\Radius\RadAcct::online()
                ->orderByDesc('acctstarttime')
                ->get(['radacctid', 'username', 'framedipaddress', 'callingstationid', 'nasipaddress', 'acctstarttime', 'acctsessiontime', 'acctinputoctets', 'acctoutputoctets'])
                ->unique('username')
                ->keyBy('username');
        } catch (\Exception $e) {
            $sessions = collect();
        }

        // 2. Cocokkan dengan ONT pelanggan (voucher tidak punya ONT → otomatis terfilter)
        $customers = collect();
        if ($sessions->isNotEmpty()) {
            $customers = Customer::with(['package', 'areaModel', 'ont'])
                ->whereHas('ont', fn ($q) => $q->whereIn('pppoe_user', $sessions->keys()->all()))
                ->whereDoesntHave('package', fn ($q) => $q->where('access_mode', 'voucher'))
                ->when(auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_all_view_all'), function ($q) {
                    if (auth()->user()->can('customers_all_view_area')) {
                        $q->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
                    } else {
                        $q->where('sales_id', auth()->id());
                    }
                })
                ->when($request->area_id, fn ($q, $area) => $q->where('area_id', $area))
                ->when($request->package_id, fn ($q, $pkg) => $q->where('package_id', $pkg))
                ->get();
        }

        // 3. Gabungkan data pelanggan + sesi
        $rows = $customers->map(function (Customer $c) use ($sessions) {
            $username = $c->ont?->pppoe_user;
            $s = $sessions->get($username);
            return [
                'id' => $c->id,
                'customer_code' => $c->customer_code,
                'name' => $c->name,
                'area' => $c->areaModel?->name ?? $c->area,
                'package' => $c->package?->name,
                'username' => $username,
                'access_mode' => $c->package?->access_mode ?? $c->ont?->access_mode,
                'ip_address' => $s?->framedipaddress,
                'mac_address' => $s?->callingstationid,
                'nas_ip' => $s?->nasipaddress,
                'start_time' => $s?->acctstarttime ? (string) $s->acctstarttime : null,
                'session_time' => $s ? $s->liveSessionTime() : 0,
                'upload' => (int) ($s?->acctinputoctets ?? 0),
                'download' => (int) ($s?->acctoutputoctets ?? 0),
            ];
        });

        // 4. Pencarian dan Filter Tab
        $tab = $request->query('tab', '');
        if ($tab) {
            $rows = $rows->filter(function ($r) use ($tab) {
                return mb_strtolower($r['access_mode']) === mb_strtolower($tab);
            });
        }

        if ($search = trim((string) $request->search)) {
            $needle = mb_strtolower($search);
            $rows = $rows->filter(function ($r) use ($needle) {
                foreach (['name', 'customer_code', 'area', 'package', 'username', 'ip_address', 'mac_address'] as $f) {
                    if ($r[$f] && str_contains(mb_strtolower($r[$f]), $needle)) return true;
                }
                return false;
            });
        }

        $rows = $rows->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        // 5. Paginasi manual
        $perPage = (int) ($request->per_page ?? 20);
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Customers/Online', [
            'customers' => $paginated,
            'areas' => \App\Models\Area::orderBy('name')->get(['id', 'name']),
            'packages' => InternetPackage::where('access_mode', '!=', 'voucher')->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'area_id', 'package_id', 'tab']),
            'totalOnline' => $rows->count(),
        ]);
    }

    /**
     * Daftar pelanggan yang sedang offline (RADIUS), tanpa voucher.
     */
    public function offline(Request $request): Response
    {
        try {
            $onlineUsernames = \App\Models\Radius\RadAcct::online()->pluck('username')->toArray();
        } catch (\Exception $e) {
            $onlineUsernames = [];
        }

        $customers = Customer::with(['package', 'areaModel', 'ont'])
            ->whereIn('status', ['active', 'suspended'])
            ->whereDoesntHave('package', fn ($q) => $q->where('access_mode', 'voucher'))
            ->whereHas('ont', function ($q) use ($onlineUsernames) {
                if (!empty($onlineUsernames)) {
                    $q->whereNotIn('pppoe_user', $onlineUsernames);
                }
            })
            ->when(auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_all_view_all'), function ($q) {
                if (auth()->user()->can('customers_all_view_area')) {
                    $q->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
                } else {
                    $q->where('sales_id', auth()->id());
                }
            })
            ->when($request->area_id, fn ($q, $area) => $q->where('area_id', $area))
            ->when($request->package_id, fn ($q, $pkg) => $q->where('package_id', $pkg))
            ->get();

        $offlineUsernames = $customers->pluck('ont.pppoe_user')->filter()->toArray();
        $offlineSessions = collect();
        if (!empty($offlineUsernames)) {
            try {
                $offlineSessions = \App\Models\Radius\RadAcct::whereIn('username', $offlineUsernames)
                    ->orderByDesc('acctstoptime')
                    ->get()
                    ->unique('username')
                    ->keyBy('username');
            } catch (\Exception $e) {
                // ignore
            }
        }

        $rows = $customers->map(function (Customer $c) use ($offlineSessions) {
            $username = $c->ont?->pppoe_user;
            $s = $offlineSessions->get($username);
            return [
                'id' => $c->id,
                'customer_code' => $c->customer_code,
                'name' => $c->name,
                'area' => $c->areaModel?->name ?? $c->area,
                'package' => $c->package?->name,
                'username' => $username,
                'access_mode' => $c->package?->access_mode ?? $c->ont?->access_mode,
                'mac_address' => $s?->callingstationid,
                'last_logout' => $s?->acctstoptime ? (string) $s->acctstoptime : null,
                'status' => $c->status,
            ];
        });

        $tab = $request->query('tab', '');
        if ($tab) {
            $rows = $rows->filter(function ($r) use ($tab) {
                return mb_strtolower($r['access_mode']) === mb_strtolower($tab);
            });
        }

        if ($search = trim((string) $request->search)) {
            $needle = mb_strtolower($search);
            $rows = $rows->filter(function ($r) use ($needle) {
                foreach (['name', 'customer_code', 'area', 'package', 'username', 'mac_address'] as $f) {
                    if ($r[$f] && str_contains(mb_strtolower($r[$f]), $needle)) return true;
                }
                return false;
            });
        }

        $rows = $rows->sortByDesc('last_logout')->values();

        $perPage = (int) ($request->per_page ?? 20);
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Customers/Offline', [
            'customers' => $paginated,
            'areas' => \App\Models\Area::orderBy('name')->get(['id', 'name']),
            'packages' => \App\Models\InternetPackage::where('access_mode', '!=', 'voucher')->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'area_id', 'package_id', 'tab']),
            'totalOffline' => $rows->count(),
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

    public function canceled(Request $request): Response
    {
        $query = Customer::where('status', 'canceled')
            ->when(auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_booking_view_all'), function($q) {
                if (auth()->user()->can('customers_booking_view_area')) {
                    $q->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
                } else {
                    $q->where('sales_id', auth()->id());
                }
            })
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });

        $customers = (clone $query)
            ->with(['package', 'sales', 'areaModel', 'user'])
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => (clone $query)->count(),
        ];

        return Inertia::render('Customers/Canceled', [
            'customers' => $customers,
            'stats' => $stats,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Halaman Survey (status = survey)
     */
    public function survey(Request $request): Response
    {
        $tab = $request->tab ?? 'semua';
        
        $baseQuery = Customer::survey()
            ->with(['package', 'surveys.odp', 'surveys.surveyor', 'technicianSchedules.technician'])
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
            
        // Ambil semua user yang aktif (sesuai request untuk menampilkan semua karyawan)
        $technicians = User::where('is_active', true)->get();

        // Get all areas for dropdown
        $areas = \App\Models\Area::orderBy('name')->pluck('name')->toArray();

        return Inertia::render('Customers/Survey', [
            'customers' => $customers,
            'availableOdps' => Odp::active()->with(['odc.olt', 'ports'])->get(),
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

        // Ambil semua user yang aktif (sesuai request untuk menampilkan semua karyawan)
        $technicians = User::where('is_active', true)->get();
        $availableOnts = \App\Models\Ont::whereIn('status', ['Sudah Set', 'Belum Set/Baru Input', 'inactive'])
            ->whereNull('customer_id')
            ->get();
            
        $materialTransactions = \App\Models\MaterialTransaction::where('type', 'in')
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
            'taxSettings' => [
                'tax_ppn' => \App\Models\Setting::get('tax_ppn', '0'),
                'tax_bhp' => \App\Models\Setting::get('tax_bhp', '0'),
                'tax_uso' => \App\Models\Setting::get('tax_uso', '0'),
            ],
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
            'taxSettings' => [
                'tax_ppn' => \App\Models\Setting::get('tax_ppn', '0'),
                'tax_bhp' => \App\Models\Setting::get('tax_bhp', '0'),
                'tax_uso' => \App\Models\Setting::get('tax_uso', '0'),
            ],
        ]);
    }

    /**
     * Halaman Pelanggan Aktif
     */
    public function active(Request $request): Response
    {
        // Exclude pure resellers (customers without a monthly package)
        $baseQuery = Customer::where('status', 'active')->whereNotNull('package_id');
        
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
            'onlineUsernames' => (function() {
                try {
                    return \App\Models\Radius\RadAcct::online()->pluck('username')->toArray();
                } catch (\Exception $e) {
                    return [];
                }
            })(),
        ]);
    }

    /**
     * Halaman Pelanggan Isolir
     */
    public function isolir(Request $request): Response
    {
        // Exclude pure resellers (customers without a monthly package)
        $baseQuery = Customer::where('status', 'suspended')->whereNotNull('package_id');
        
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
            'isolir' => (clone $baseQuery)->count(),
        ];

        $customers = (clone $baseQuery)
            ->with(['package', 'surveys.odp', 'ont.odp.odc.olt', 'technicianSchedules' => function ($q) {
                $q->where('type', 'installation')->with('technician');
            }])
            ->search($request->search)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Isolir', [
            'customers' => $customers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'tab', 'date', 'area']),
            'onlineUsernames' => (function() {
                try {
                    return \App\Models\Radius\RadAcct::online()->pluck('username')->toArray();
                } catch (\Exception $e) {
                    return [];
                }
            })(),
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
            'is_reseller' => 'boolean',
            'service_status' => 'nullable|in:berbayar,gratis',
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

        DB::transaction(function () use ($validated, &$customer) {
            $customer = Customer::create($validated);

            if (!empty($validated['is_reseller']) && $validated['is_reseller']) {
                $customer->reseller()->create([
                    'balance' => 0,
                    'is_active' => true, // Default to true, or handle differently based on status? Let's true for now.
                ]);
            }
            
            \App\Models\AuditLog::createLog('Create Booking', $customer, null, 'booking', 'Membuat booking baru');
        });

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
            'user',
        ]);

        $materialRequests = \App\Models\MaterialRequest::with('items.material')
            ->where('customer_id', $customer->id)
            ->latest()
            ->get();

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
            'source' => request()->query('source'),
            'availableOdps' => Odp::active()->with(['odc.olt', 'onts.customer'])->get(),
            'availableOnts' => Ont::where('status', 'Sudah Set')->whereNull('customer_id')->get(),
            'materials' => \App\Models\Material::orderBy('name')->get(),
            'materialRequests' => $materialRequests,
        ]);
    }

    /**
     * Buat akun login untuk pelanggan
     */
    public function createAccount(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($customer->user_id) {
            return back()->with('error', 'Pelanggan ini sudah memiliki akun.');
        }

        DB::transaction(function () use ($customer, $validated) {
            $user = User::create([
                'name' => $customer->name,
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role' => 'customer',
                'is_active' => true,
            ]);

            // Assign Spatie role (buat jika belum ada)
            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer']);
            $user->assignRole($role);

            // Update customer dengan user_id yang baru
            $customer->update(['user_id' => $user->id]);
        });

        return back()->with('success', 'Akun login pelanggan berhasil dibuat.');
    }

    /**
     * Update akun login pelanggan (password dan email)
     */
    public function resetPassword(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'nullable|email|unique:users,email,' . $customer->user_id,
            'password' => 'nullable|string|min:8',
        ]);

        if (!$customer->user_id || !$customer->user) {
            return back()->with('error', 'Pelanggan ini belum memiliki akun.');
        }

        $userUpdates = [];
        if (!empty($validated['password'])) {
            $userUpdates['password'] = bcrypt($validated['password']);
        }
        if (!empty($validated['email'])) {
            $userUpdates['email'] = $validated['email'];
            // Update customer email to match
            $customer->update(['email' => $validated['email']]);
        }

        if (!empty($userUpdates)) {
            $customer->user->update($userUpdates);
            return back()->with('success', 'Data akun login pelanggan berhasil diubah.');
        }

        return back()->with('info', 'Tidak ada perubahan pada akun login.');
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
            'email' => [
                'nullable',
                'email',
                'max:255',
                function ($attribute, $value, $fail) use ($customer) {
                    if ($customer->user_id) {
                        $exists = \App\Models\User::where('email', $value)
                            ->where('id', '!=', $customer->user_id)
                            ->exists();
                        if ($exists) {
                            $fail('Email ini sudah digunakan oleh akun login lain.');
                        }
                    }
                }
            ],
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
            'is_reseller' => 'boolean',
            'service_status' => 'nullable|in:berbayar,gratis',
            'tax_ppn' => 'nullable|numeric|min:0|max:100',
            'tax_bhp' => 'nullable|numeric|min:0|max:100',
            'tax_uso' => 'nullable|numeric|min:0|max:100',
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

        DB::transaction(function () use ($customer, $validated) {
            $customer->update($validated);
            
            // Sync email login if account exists and email changed
            if ($customer->user_id && isset($validated['email'])) {
                // Pastikan email baru tidak dipakai oleh user lain
                $emailExists = \App\Models\User::where('email', $validated['email'])
                    ->where('id', '!=', $customer->user_id)
                    ->exists();
                
                if (!$emailExists) {
                    $customer->user->update(['email' => $validated['email']]);
                }
            }
            
            if (!empty($validated['is_reseller']) && $validated['is_reseller']) {
                $customer->reseller()->firstOrCreate([
                    'balance' => 0,
                    'is_active' => true,
                ]);
            } else {
                if ($customer->reseller) {
                    $customer->reseller()->delete();
                }
            }
        });

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

            // Hapus Material Transactions yang terkait
            \App\Models\MaterialTransaction::where('customer_id', $customer->id)->delete();

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

        // Check if there are any pending material requests
        $pendingRequest = \App\Models\MaterialRequest::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->first();
            
        if ($pendingRequest) {
            return back()->withErrors(['error' => 'Tidak dapat menyelesaikan instalasi karena ada Request Material Tambahan yang belum disetujui Admin.']);
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

        $usageDetails = []; DB::transaction(function () use ($validated, $customer, $odp, $request, $ont, &$usageDetails) {
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

            $newOdp = Odp::find($validated['odp_id']);
            // Update used_ports di ODP baru ONLY if it is a new assignment or port changed
            if ($isNewAssignment && $newOdp) {
                $newOdp->increment('used_ports');
            }

            // Update odp_ports if exists
            if ($newOdp) {
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
            }

            // Update status jadwal teknisi ke done jika ada
            $schedules = $customer->technicianSchedules()->where('type', 'installation')->whereIn('status', ['scheduled', 'done'])->get();
            
            // Restore previous usage from old notes if this is an edit to prevent double-deduction
            $oldUsages = [];
            foreach ($schedules as $schedule) {
                if ($schedule->status === 'done' && $schedule->notes) {
                    $lines = explode("\n", $schedule->notes);
                    foreach ($lines as $line) {
                        if (str_starts_with(trim($line), 'Material:')) {
                            $mats = explode(',', str_replace('Material:', '', $line));
                            foreach ($mats as $mat) {
                                $mat = trim($mat);
                                if (preg_match('/^(.*?)\s*\((\d+(\.\d+)?)\s*(.*?)\)$/', $mat, $matches)) {
                                    $oldUsages[] = [
                                        'name' => trim($matches[1]),
                                        'qty' => (float)$matches[2]
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            // Restore old usages back to Area Stock
            foreach ($oldUsages as $old) {
                $nameLower = strtolower($old['name']);
                $cleanName = trim(preg_replace('/\s*\(.*?\)\s*/', '', $nameLower));
                $material = \App\Models\Material::where('name', 'like', "%{$cleanName}%")->first();
                if (!$material && str_contains($nameLower, 'kabel')) {
                    $material = \App\Models\Material::where('category', 'Kabel Drop')
                        ->orWhere('category', 'Kabel')
                        ->orWhere('name', 'like', '%kabel%')->first();
                }
                if ($material) {
                    $materialStock = \App\Models\MaterialStock::where('material_id', $material->id)
                        ->where('area_id', $customer->area_id)
                        ->first();
                    if ($materialStock) {
                        $materialStock->increment('stock', $old['qty']);
                    }
                }
            }

                // Handle material usage
            if ($request->has('materials_returned') && is_array($request->materials_returned)) {
                $items = $request->materials_returned;
                $usedItems = [];
                $returnItems = [];
                $rincianNotesUsed = [];
                $rincianNotesReturn = [];

                foreach ($items as $item) {
                    $assignedQty = isset($item['assigned_qty']) ? (float)$item['assigned_qty'] : 0;
                    $usedQty = isset($item['used_qty']) ? (float)$item['used_qty'] : 0;
                    
                    if ($assignedQty == 0 && $usedQty == 0) continue;

                    $nameLower = strtolower($item['name']);
                    $cleanName = trim(preg_replace('/\s*\(.*?\)\s*/', '', $nameLower));
                    $material = \App\Models\Material::where('name', 'like', "%{$cleanName}%")->first();
                    
                    if (!$material && str_contains($cleanName, 'kabel')) {
                        $material = \App\Models\Material::where('category', 'Kabel Drop')
                            ->orWhere('category', 'Kabel')
                            ->orWhere('name', 'like', '%kabel%')->first();
                    }

                    if ($material) {
                        $unitStr = str_contains($nameLower, 'kabel') ? 'meter' : 'pcs';
                        
                        // 1. Catat pemakaian asli (Instalasi)
                        if ($usedQty > 0) {
                            $usedItems[] = [
                                'material' => $material,
                                'qty' => $usedQty,
                                'unit' => $unitStr
                            ];
                            $rincianNotesUsed[] = "- {$material->name}: {$usedQty} {$unitStr} (Bekal awal: {$assignedQty} {$unitStr})";
                            $usageDetails[] = $material->name . ' (' . $usedQty . ' ' . $unitStr . ')';
                        }
                        
                        // 2. Jika ada sisa, buatkan pengembalian pending
                        if ($usedQty < $assignedQty) {
                            $excessQty = $assignedQty - $usedQty;
                            $returnItems[] = [
                                'material' => $material,
                                'qty' => $excessQty,
                                'unit' => $unitStr
                            ];
                            $rincianNotesReturn[] = "- {$material->name}: SISA (Dikembalikan): {$excessQty} {$unitStr}";
                        }
                    }
                }

                // Proses Instalasi (Memotong Stok Area sesuai Pemakaian)
                if (count($usedItems) > 0) {
                    $transactionOut = \App\Models\MaterialTransaction::create([
                        'transaction_number' => 'OUT-INSTALASI-' . date('YmdHis'),
                        'type' => 'out',
                        'status' => 'approved',
                        'date' => now(),
                        'technician_name' => auth()->user()->name,
                        'purpose' => 'Pemakaian Material Instalasi Pelanggan ' . $customer->name,
                        'user_id' => auth()->id(),
                        'customer_id' => $customer->id,
                        'area_id' => $customer->area_id,
                        'notes' => "Rincian Pemakaian Instalasi:\n" . implode("\n", $rincianNotesUsed)
                    ]);

                    foreach ($usedItems as $ui) {
                        $materialStock = \App\Models\MaterialStock::where('material_id', $ui['material']->id)
                                        ->where('area_id', $customer->area_id)
                                        ->first();
                        
                        $stockBefore = $materialStock ? $materialStock->stock : 0;
                        $stockAfter = $stockBefore - $ui['qty'];

                        \App\Models\MaterialTransactionItem::create([
                            'material_transaction_id' => $transactionOut->id,
                            'material_id' => $ui['material']->id,
                            'quantity' => $ui['qty'],
                            'unit' => $ui['unit'],
                            'price_per_unit' => $ui['material']->price_per_unit ?? 0,
                            'total_price' => ($ui['material']->price_per_unit ?? 0) * $ui['qty'],
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'condition' => 'Terpasang/Digunakan'
                        ]);

                        if ($materialStock) {
                            $materialStock->decrement('stock', $ui['qty']);
                        } else {
                            \App\Models\MaterialStock::create([
                                'material_id' => $ui['material']->id,
                                'area_id' => $customer->area_id,
                                'stock' => -$ui['qty'],
                                'initial_stock' => 0,
                                'total_rolls' => 0,
                                'total_packs' => 0,
                                'total_pieces' => 0
                            ]);
                        }
                    }
                }

                // Proses Pengembalian (Pending - belum menambah gudang atau memotong area)
                if (count($returnItems) > 0) {
                    $transactionReturn = \App\Models\MaterialTransaction::create([
                        'transaction_number' => 'RTR-EXCESS-' . date('YmdHis'),
                        'type' => 'out',
                        'status' => 'pending',
                        'date' => now(),
                        'technician_name' => auth()->user()->name,
                        'purpose' => 'Pengembalian Sisa Material Instalasi Pelanggan ' . $customer->name,
                        'user_id' => auth()->id(),
                        'customer_id' => $customer->id,
                        'area_id' => $customer->area_id,
                        'notes' => "Rincian Hitungan Sisa:\n" . implode("\n", $rincianNotesReturn)
                    ]);

                    foreach ($returnItems as $ri) {
                        \App\Models\MaterialTransactionItem::create([
                            'material_transaction_id' => $transactionReturn->id,
                            'material_id' => $ri['material']->id,
                            'quantity' => $ri['qty'],
                            'unit' => $ri['unit'],
                            'price_per_unit' => $ri['material']->price_per_unit ?? 0,
                            'total_price' => ($ri['material']->price_per_unit ?? 0) * $ri['qty'],
                            'condition' => 'Layak Pakai'
                        ]);
                    }
                }
            }
            
            // Update status jadwal teknisi ke done jika ada dan update notes
            foreach ($schedules as $schedule) {
                $newNotes = $schedule->notes;
                if (count($usageDetails) > 0) {
                    // Remove old Material lines
                    $lines = explode("\n", $newNotes);
                    $lines = array_filter($lines, function($line) {
                        return !str_starts_with(trim($line), 'Material:');
                    });
                    $lines[] = "Material: " . implode(', ', $usageDetails);
                    $newNotes = implode("\n", $lines);
                }
                $schedule->update([
                    'status' => 'done',
                    'notes' => $newNotes
                ]);
            }
        });

        $ont = Ont::where('customer_id', $customer->id)->first();
        $usageStr = count($usageDetails) > 0 ? " | Material: " . implode(', ', $usageDetails) : "";
        \App\Models\AuditLog::createLog('Laporan Instalasi', $customer, 'installing', 'installing', 'Mengisi laporan instalasi ONT ' . ($ont ? $ont->serial_number : '') . $usageStr);

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
    public function requestMaterial(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'notes' => 'nullable|string'
        ]);

        DB::transaction(function () use ($customer, $validated) {
            $materialRequest = \App\Models\MaterialRequest::create([
                'request_number' => 'REQ-MAT-' . date('YmdHis') . '-' . strtoupper(\Illuminate\Support\Str::random(4)),
                'customer_id' => $customer->id,
                'user_id' => auth()->id(),
                'area_id' => $customer->area_id,
                'status' => 'pending',
                'notes' => $validated['notes'],
            ]);

            foreach ($validated['items'] as $item) {
                \App\Models\MaterialRequestItem::create([
                    'material_request_id' => $materialRequest->id,
                    'material_id' => $item['material_id'],
                    'quantity' => $item['quantity'],
                ]);
            }
        });

        return back()->with('success', 'Request tambahan material berhasil diajukan dan menunggu persetujuan Admin.');
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
            'free_hotspot' => 'nullable|boolean',
            'hotspot_user' => 'nullable|string',
            'hotspot_password' => 'nullable|string',
            'hotspot_vlan_id' => 'nullable|string',
            'ip_login' => 'nullable|string',
            'login_user' => 'nullable|string',
            'login_password' => 'nullable|string',
            'notes' => 'nullable|string',
            'tax_ppn' => 'nullable|numeric|min:0|max:100',
            'tax_bhp' => 'nullable|numeric|min:0|max:100',
            'tax_uso' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($customer->status === 'installing' && $customer->is_audited && $customer->ont) {
            DB::transaction(function () use ($customer, $validated) {
                $customer->update([
                    'status' => 'active',
                    'activation_date' => $validated['activation_date'],
                    'notes' => $validated['notes'] ? $customer->notes . "\n[Aktivasi]: " . $validated['notes'] : $customer->notes,
                    'tax_ppn' => $validated['tax_ppn'] ?? null,
                    'tax_bhp' => $validated['tax_bhp'] ?? null,
                    'tax_uso' => $validated['tax_uso'] ?? null,
                ]);

                $customer->ont->update([
                    'pppoe_user' => $validated['pppoe_user'],
                    'pppoe_password' => $validated['pppoe_password'],
                    'vlan_mode' => $validated['vlan_mode'],
                    'vlan_id' => $validated['vlan_id'],
                    'access_mode' => $validated['access_mode'],
                    'free_hotspot' => $validated['free_hotspot'] ?? false,
                    'hotspot_user' => $validated['hotspot_user'] ?? null,
                    'hotspot_password' => $validated['hotspot_password'] ?? null,
                    'hotspot_vlan_id' => $validated['hotspot_vlan_id'] ?? null,
                    'ip_login' => $validated['ip_login'],
                    'login_user' => $validated['login_user'],
                    'login_password' => $validated['login_password'],
                ]);

                // Create initial invoice
                $activationDate = \Carbon\Carbon::parse($validated['activation_date']);
                $amount = $customer->package ? $customer->package->price : 0;
                $isProrata = false;

                $billingType = \App\Models\Setting::get('billing_type', 'prabayar');
                $prorataFormula = \App\Models\Setting::get('prorata_formula', 'exact_days');
                $issueDateSetting = (int) \App\Models\Setting::get('invoice_issue_date', '1');
                $isolateDays = (int) \App\Models\Setting::get('isolate_days', '3');
                $isolateTime = \App\Models\Setting::get('isolate_time', '00:00');

                if ($billingType === 'prorata' && $amount > 0) {
                    $nextBillingDate = $activationDate->copy();
                    if ($activationDate->day >= $issueDateSetting) {
                        $nextBillingDate->addMonth()->day($issueDateSetting);
                    } else {
                        $nextBillingDate->day($issueDateSetting);
                    }
                    
                    $prevBillingDate = $nextBillingDate->copy()->subMonth();
                    $totalDaysInCycle = $nextBillingDate->diffInDays($prevBillingDate);
                    $daysUsed = $nextBillingDate->diffInDays($activationDate);
                    
                    if ($daysUsed > 0 && $totalDaysInCycle > 0 && $daysUsed < $totalDaysInCycle) {
                        if ($prorataFormula === 'fixed_30') {
                            $amount = ($amount / 30) * $daysUsed;
                        } elseif ($prorataFormula === 'mid_month') {
                            $midPoint = $prevBillingDate->copy()->addDays(floor($totalDaysInCycle / 2));
                            if ($activationDate->gt($midPoint)) {
                                $amount = $amount / 2;
                            } else {
                                $amount = $amount; // Full price
                            }
                        } else {
                            // default: exact_days
                            $amount = ($amount / $totalDaysInCycle) * $daysUsed;
                        }
                        
                        $amount = round($amount);
                        $isProrata = true;
                    }
                }
                
                $dueDateTime = $activationDate->copy()->addDays($isolateDays);
                $timeParts = explode(':', $isolateTime);
                if (count($timeParts) == 2) {
                    $dueDateTime->setTime((int)$timeParts[0], (int)$timeParts[1], 0);
                }

                $status = 'unpaid';
                if ($customer->service_status === 'gratis') {
                    $amount = 0;
                    $status = 'paid';
                } else {
                    $taxPpn = (float) ($validated['tax_ppn'] ?? \App\Models\Setting::get('tax_ppn', '0'));
                    $taxBhp = (float) ($validated['tax_bhp'] ?? \App\Models\Setting::get('tax_bhp', '0'));
                    $taxUso = (float) ($validated['tax_uso'] ?? \App\Models\Setting::get('tax_uso', '0'));
                    
                    $totalTaxPercent = $taxPpn + $taxBhp + $taxUso;
                    if ($totalTaxPercent > 0) {
                        $amount = $amount + ($amount * ($totalTaxPercent / 100));
                    }
                }

                \App\Models\Invoice::create([
                    'customer_id' => $customer->id,
                    'period_month' => $activationDate->month,
                    'period_year' => $activationDate->year,
                    'amount' => $amount,
                    'due_date' => $dueDateTime,
                    'issued_date' => $activationDate,
                    'status' => $status,
                    'is_prorata' => $isProrata,
                ]);

                // Auto Incentives: Fee Market/Booking for Sales
                if ($customer->sales_id) {
                    $salesUser = \App\Models\User::find($customer->sales_id);
                    if ($salesUser) {
                        $feeAmount = $salesUser->booking_fee > 0 ? $salesUser->booking_fee : \App\Models\MasterFee::where('type', 'Fee Booking')->where('is_active', true)->value('nominal');
                        if ($feeAmount > 0) {
                            \App\Models\Incentive::create([
                                'user_id' => $salesUser->id,
                                'amount' => $feeAmount,
                                'type' => 'auto',
                                'description' => 'Fee Booking Pelanggan: ' . $customer->name,
                                'incentive_date' => now(),
                                'status' => 'pending'
                            ]);
                        }
                    }
                }

                // Auto Incentives: Fee Pasang for Technician
                $installationSchedule = $customer->technicianSchedules()->where('type', 'installation')->latest()->first();
                if ($installationSchedule && $installationSchedule->technician_id) {
                    $techUser = \App\Models\User::find($installationSchedule->technician_id);
                    if ($techUser) {
                        $feeAmount = $techUser->installation_fee > 0 ? $techUser->installation_fee : \App\Models\MasterFee::where('type', 'Fee Pasang')->where('is_active', true)->value('nominal');
                        if ($feeAmount > 0) {
                            \App\Models\Incentive::create([
                                'user_id' => $techUser->id,
                                'amount' => $feeAmount,
                                'type' => 'auto',
                                'description' => 'Fee Pasang Pelanggan: ' . $customer->name,
                                'incentive_date' => now(),
                                'status' => 'pending'
                            ]);
                        }
                    }
                }
            });

            \App\Models\AuditLog::createLog('Aktivasi Layanan', $customer, 'installing', 'active', 'Mengaktifkan layanan pelanggan');

            return redirect()->route('customers.installed')->with('success', 'Pelanggan berhasil diaktivasi!');
        }
        return redirect()->back()->with('error', 'Pelanggan belum siap diaktivasi.');
    }

    public function updateOntInline(Request $request, Customer $customer)
    {
        // Check if there are any pending material requests
        $pendingRequest = \App\Models\MaterialRequest::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->first();
            
        if ($pendingRequest) {
            return back()->with('error', 'Tidak dapat menyelesaikan instalasi karena ada Request Material Tambahan yang belum disetujui Admin.');
        }

        $validated = $request->validate([
            'pppoe_user' => 'nullable|string',
            'pppoe_password' => 'nullable|string',
            'vlan_mode' => 'nullable|string',
            'vlan_id' => 'nullable|string',
            'access_mode' => 'nullable|string',
            'free_hotspot' => 'nullable|boolean',
            'hotspot_user' => 'nullable|string',
            'hotspot_password' => 'nullable|string',
            'hotspot_vlan_id' => 'nullable|string',
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
            'odp_id' => 'required_if:feasibility,feasible|exists:odps,id',
            'port_number' => 'required_if:feasibility,feasible|integer',
            'distance_meters' => 'required_if:feasibility,feasible|numeric',
            'port_available' => 'nullable|boolean',
            'feasibility' => 'required|in:feasible,not_feasible',
            'notes' => 'nullable|string',
            'photos' => 'required|array|min:1',
            'photos.*.label' => 'required|string',
            'photos.*.file' => 'required|image|max:5120',
        ], [
            'odp_id.required_if' => 'ODP harus dipilih jika status Feasible.',
            'port_number.required_if' => 'Port ODP harus dipilih jika status Feasible.',
            'distance_meters.required_if' => 'Jarak kabel harus diisi jika status Feasible.',
            'photos.*.file.required' => 'Semua foto dokumentasi wajib diunggah.',
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
            
            if (!empty($validated['port_number'])) {
                // Check if port is available
                $port = \App\Models\OdpPort::where('odp_id', $odp->id)->where('port_number', $validated['port_number'])->first();
                if ($port && in_array($port->status, ['used', 'reserved', 'fault', 'stop'])) {
                    return back()->withErrors(['port_number' => 'Port ' . $validated['port_number'] . ' sudah digunakan/tidak tersedia.']);
                }
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

        $survey = Survey::create($validated);

        if (!empty($validated['odp_id']) && !empty($validated['port_number'])) {
            \App\Models\OdpPort::updateOrCreate(
                ['odp_id' => $validated['odp_id'], 'port_number' => $validated['port_number']],
                ['status' => 'reserved']
            );
        }
        
        // Update schedule status if any
        $schedule = $customer->technicianSchedules()->where('type', 'survey')->where('status', 'scheduled')->first();
        if ($schedule) {
            $schedule->update(['status' => 'done']);
        }

        // Auto Incentive: Fee Survey
        $surveyFee = \App\Models\MasterFee::where('type', 'Fee Survey')->where('is_active', true)->value('nominal');
        if ($surveyFee > 0) {
            \App\Models\Incentive::create([
                'user_id' => $validated['surveyor_id'],
                'amount' => $surveyFee,
                'type' => 'auto',
                'description' => 'Fee Survey Pelanggan: ' . $customer->name,
                'incentive_date' => now(),
                'status' => 'pending'
            ]);
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

    public function updateSurvey(Request $request, Customer $customer): RedirectResponse
    {
        if (!auth()->user()->can('customers_survey_report')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah laporan survey.');
        }

        $survey = $customer->surveys()->first();
        if (!$survey) {
            return back()->withErrors(['message' => 'Laporan survey tidak ditemukan.']);
        }

        $validated = $request->validate([
            'surveyor_id' => 'required|exists:users,id',
            'odp_id' => 'required_if:feasibility,feasible|exists:odps,id',
            'port_number' => 'required_if:feasibility,feasible|integer',
            'distance_meters' => 'required_if:feasibility,feasible|numeric',
            'port_available' => 'nullable|boolean',
            'feasibility' => 'required|in:feasible,not_feasible',
            'notes' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*.label' => 'nullable|string',
            'photos.*.file' => 'nullable|image|max:5120',
        ]);

        if (!empty($validated['odp_id'])) {
            $odp = Odp::findOrFail($validated['odp_id']);
            
            if ($survey->odp_id != $validated['odp_id'] || $survey->port_number != $validated['port_number']) {
                $pendingCount = \App\Models\Customer::whereIn('status', ['survey', 'installing'])
                    ->where('id', '!=', $customer->id)
                    ->whereHas('surveys', function($q) use ($odp) {
                        $q->where('odp_id', $odp->id);
                    })
                    ->whereDoesntHave('ont', function($q) use ($odp) {
                        $q->where('odp_id', $odp->id);
                    })->count();

                $availablePorts = $odp->total_ports - $odp->used_ports - $pendingCount;

                if ($availablePorts <= 0) {
                    return back()->withErrors(['odp_id' => 'ODP ini sudah penuh.']);
                }
                
                if (!empty($validated['port_number'])) {
                    $port = \App\Models\OdpPort::where('odp_id', $odp->id)->where('port_number', $validated['port_number'])->first();
                    if ($port && in_array($port->status, ['used', 'reserved', 'fault', 'stop'])) {
                        return back()->withErrors(['port_number' => 'Port ' . $validated['port_number'] . ' sudah digunakan/tidak tersedia.']);
                    }
                }
            }
        }

        // Release old port if changed
        if ($survey->odp_id && $survey->port_number) {
            if ($survey->odp_id != $request->odp_id || $survey->port_number != $request->port_number) {
                \App\Models\OdpPort::where('odp_id', $survey->odp_id)
                    ->where('port_number', $survey->port_number)
                    ->update(['status' => 'available']);
            }
        }

        $photoPaths = $survey->photos ?? [];
        if ($request->has('photos') && is_array($request->photos)) {
            $newPhotos = [];
            foreach ($request->photos as $index => $photoItem) {
                if (isset($photoItem['file']) && $photoItem['file'] instanceof \Illuminate\Http\UploadedFile) {
                    $path = $photoItem['file']->store('surveys', 'public');
                    $newPhotos[] = [
                        'label' => $photoItem['label'] ?? ('Foto ' . ($index + 1)),
                        'path' => $path,
                    ];
                } elseif (isset($photoPaths[$index])) {
                    $newPhotos[] = $photoPaths[$index];
                }
            }
            if (count($newPhotos) > 0) {
                $photoPaths = $newPhotos;
            }
        }

        $validated['photos'] = empty($photoPaths) ? null : $photoPaths;

        $survey->update($validated);

        if (!empty($validated['odp_id']) && !empty($validated['port_number'])) {
            \App\Models\OdpPort::updateOrCreate(
                ['odp_id' => $validated['odp_id'], 'port_number' => $validated['port_number']],
                ['status' => 'reserved']
            );
        }

        if ($validated['feasibility'] === 'feasible') {
            $customer->update(['status' => 'survey']);
            \App\Models\AuditLog::createLog('Laporan Survey', $customer, 'survey', 'survey', 'Mengubah laporan survey (feasible)');
        } else {
            $customer->update(['status' => 'terminated']);
            \App\Models\AuditLog::createLog('Laporan Survey', $customer, 'survey', 'terminated', 'Mengubah laporan survey (not feasible)');
        }

        return redirect()->route('customers.survey')->with('success', 'Hasil survey berhasil diubah.');
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

    public function cancel(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate([
            'cancel_reason' => 'required|string|max:1000',
            'cancel_date' => 'required|date',
        ]);

        $customer->update([
            'status' => 'canceled',
            'cancel_reason' => $request->cancel_reason,
            'cancel_date' => $request->cancel_date,
        ]);

        return redirect()->back()->with('success', 'Pendaftaran pelanggan berhasil dibatalkan.');
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

