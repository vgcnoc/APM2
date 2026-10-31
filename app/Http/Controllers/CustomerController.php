<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Odp;
use App\Models\Ont;
use App\Models\Survey;
use App\Models\TechnicianSchedule;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\RadiusService;
use App\Services\DataScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function connectionAccess(Request $request, Customer $customer, \App\Services\CustomerAccessService $access): RedirectResponse
    {
        $user = $request->user();
        $isAdmin = $user && (in_array($user->role, ['admin', 'super admin', 'superadmin', 'super-admin'], true)
            || $user->hasAnyRole(['admin', 'super admin']));
        abort_unless($user && ($isAdmin || $user->can('customers_active_suspend')), 403);
        abort_unless($isAdmin || DataScopeService::applyCustomerScope(Customer::query(), $user)->whereKey($customer->id)->exists(), 403);
        $validated = $request->validate(['disabled' => ['required', 'boolean']]);
        $disabled = (bool) $validated['disabled'];
        if (!$disabled && in_array($customer->status, ['terminated', 'canceled'], true)) {
            return back()->with('error', 'Pelanggan yang sudah berhenti atau dibatalkan harus diaktivasi ulang terlebih dahulu.');
        }
        $old = (bool) $customer->connection_disabled;
        try {
            $result = $access->setDisabled($customer, $disabled);
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            return back()->with('error', 'Perubahan koneksi pelanggan sedang diproses. Coba kembali sesaat lagi.');
        }
        \App\Models\AuditLog::createLog($disabled ? 'Disable Koneksi' : 'Enable Koneksi', $customer,
            ['connection_disabled' => $old], ['connection_disabled' => $disabled, 'pending' => !$result['complete']],
            $disabled ? 'Menonaktifkan akses koneksi pelanggan' : 'Mengaktifkan kembali akses koneksi pelanggan');
        $action = $disabled ? 'Disable' : 'Enable';
        if (!$result['complete']) {
            return back()->with('error', "$action tersimpan, tetapi penerapan belum lengkap: ".implode(' ', $result['warnings']).' Gunakan Sinkronkan Ulang.');
        }
        return back()->with('success', "$action koneksi {$customer->name} berhasil diterapkan.");
    }

    /**
     * Daftar semua pelanggan (dengan filter & pencarian)
     */
    public function index(Request $request): Response
    {
        $isolirOnly = $request->routeIs('customers.isolir');
        if ($isolirOnly) {
            // Keep this dedicated list restricted even when query parameters are changed.
            $request->merge(['status' => 'suspended']);
        }
        $baseQuery = Customer::query()
            ->where('status', '!=', 'terminated')
            ->when($isolirOnly, fn ($q) => $q->where('status', 'suspended'))
            ->when(auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_all_view_all'), function($q) {
                if (auth()->user()->can('customers_all_view_area')) {
                    $accessibleAreas = auth()->user()->getAccessibleAreaIds();
                    $q->where(function($sub) use ($accessibleAreas) {
                        $sub->whereIn('area_id', $accessibleAreas)
                            ->orWhere('sales_id', auth()->id())
                            ->orWhereHas('technicianSchedules', function($sq) {
                                $sq->where('technician_id', auth()->id());
                            })
                            ->orWhereHas('surveys', function($sq) {
                                $sq->where('surveyor_id', auth()->id());
                            });
                    });
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

        // Query konteks filter untuk statistik dan data (area, paket, layanan, ODP)
        $filterQuery = (clone $baseQuery)
            ->when($request->area || $request->area_id, fn($q) => $this->applyAreaFilter($q, $request->area_id ?: $request->area))
            ->when($request->package_id, fn ($q, $pkg) => $q->where('package_id', $pkg))
            ->when($request->service_status, function ($q, $ss) {
                if ($ss === 'berbayar') {
                    $q->where(fn ($sub) => $sub->where('service_status', 'berbayar')->orWhereNull('service_status')->orWhere('service_status', ''));
                } else {
                    $q->where('service_status', $ss);
                }
            })
            ->when($request->odp_id, fn ($q, $odp) => $q->where(fn ($sub) =>
                $sub->whereHas('ont', fn ($oq) => $oq->where('odp_id', $odp))
                    ->orWhereHas('surveys', fn ($sq) => $sq->where('odp_id', $odp))
            ));

        // 1. Hitung Statistik secara Kontekstual mengikuti filter aktif (1x agregasi SQL tunggal)
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        $agg = (clone $filterQuery)->selectRaw("
            COUNT(*) as total_all,
            COALESCE(SUM(CASE WHEN created_at < ? THEN 1 ELSE 0 END), 0) as last_month_total,
            COALESCE(SUM(CASE WHEN status = 'active' AND (connection_disabled = 0 OR connection_disabled IS NULL) THEN 1 ELSE 0 END), 0) as active_all,
            COALESCE(SUM(CASE WHEN status = 'active' AND (connection_disabled = 0 OR connection_disabled IS NULL) AND created_at < ? THEN 1 ELSE 0 END), 0) as last_month_active,
            COALESCE(SUM(CASE WHEN status IN ('booking', 'survey', 'installing') THEN 1 ELSE 0 END), 0) as pending_all,
            COALESCE(SUM(CASE WHEN status IN ('booking', 'survey', 'installing') AND created_at < ? THEN 1 ELSE 0 END), 0) as last_month_pending,
            COALESCE(SUM(CASE WHEN status = 'suspended' OR connection_disabled = 1 THEN 1 ELSE 0 END), 0) as inactive_all,
            COALESCE(SUM(CASE WHEN (status = 'suspended' OR connection_disabled = 1) AND created_at < ? THEN 1 ELSE 0 END), 0) as last_month_inactive
        ", [$startOfMonth, $startOfMonth, $startOfMonth, $startOfMonth])->first();

        $totalCustomers = (int) ($agg->total_all ?? 0);
        $lastMonthTotal = (int) ($agg->last_month_total ?? 0);
        $totalGrowth = $lastMonthTotal > 0 ? round((($totalCustomers - $lastMonthTotal) / $lastMonthTotal) * 100, 1) : ($totalCustomers > 0 ? 100 : 0);

        $activeCustomers = (int) ($agg->active_all ?? 0);
        $lastMonthActive = (int) ($agg->last_month_active ?? 0);
        $activeGrowth = $lastMonthActive > 0 ? round((($activeCustomers - $lastMonthActive) / $lastMonthActive) * 100, 1) : ($activeCustomers > 0 ? 100 : 0);
        $activePercentage = $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 1) : 0;

        $pendingCustomers = (int) ($agg->pending_all ?? 0);
        $lastMonthPending = (int) ($agg->last_month_pending ?? 0);
        $pendingGrowth = $lastMonthPending > 0 ? round((($pendingCustomers - $lastMonthPending) / $lastMonthPending) * 100, 1) : ($pendingCustomers > 0 ? 100 : 0);
        $pendingPercentage = $totalCustomers > 0 ? round(($pendingCustomers / $totalCustomers) * 100, 1) : 0;

        $inactiveCustomers = (int) ($agg->inactive_all ?? 0);
        $lastMonthInactive = (int) ($agg->last_month_inactive ?? 0);
        $inactiveGrowth = $lastMonthInactive > 0 ? round((($inactiveCustomers - $lastMonthInactive) / $lastMonthInactive) * 100, 1) : ($inactiveCustomers > 0 ? 100 : 0);
        $inactivePercentage = $totalCustomers > 0 ? round(($inactiveCustomers / $totalCustomers) * 100, 1) : 0;

        // 2. Fetch Data Pelanggan
        $customers = (clone $filterQuery)
            ->with([
                'package', 
                'ont.odp', 
                'surveys.odp', 
                'surveys.surveyor:id,name', 
                'technicianSchedules.technician:id,name', 
                'areaModel', 
                'user', 
                'sales:id,name'
            ])
            ->search($request->search)
            ->when($request->status, function ($q, $status) {
                if ($status === 'pending') {
                    $q->whereIn('status', ['booking', 'survey', 'installing']);
                } elseif ($status === 'disabled') {
                    $q->where('connection_disabled', true);
                } elseif ($status === 'inactive') {
                    $q->where(fn ($sub) => $sub->where('status', 'suspended')->orWhere('connection_disabled', true));
                } elseif ($status === 'active') {
                    $q->where('status', 'active')->where('connection_disabled', false);
                } else {
                    $q->where('status', $status);
                }
            })
            ->when($request->sort_by, function ($q, $sort) {
                return match($sort) {
                    'terlama' => $q->orderByRaw('COALESCE(registration_date, created_at) ASC, id ASC'),
                    'nama_asc' => $q->orderBy('name', 'asc'),
                    'nama_desc' => $q->orderBy('name', 'desc'),
                    'kode_asc' => $q->orderBy('customer_code', 'asc'),
                    'kode_desc' => $q->orderBy('customer_code', 'desc'),
                    default => $q->orderByRaw('COALESCE(registration_date, created_at) DESC, id DESC'),
                };
            }, fn ($q) => $q->orderByRaw('COALESCE(registration_date, created_at) DESC, id DESC'))
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'isolirOnly' => $isolirOnly,
            'customers' => $customers,
            'packages' => fn () => InternetPackage::active()->get(['id', 'name', 'price', 'speed_mbps']),
            'areas' => fn () => DataScopeService::getScopedAreas($request->user(), 'customers_all'),
            'odps' => function () use ($request) {
                $odpFilterQuery = class_exists('\App\Models\Odp') ? \App\Models\Odp::orderBy('name') : null;
                if ($odpFilterQuery) {
                    DataScopeService::applyOdpScope($odpFilterQuery, $request->user());
                    return $odpFilterQuery->get(['id', 'name', 'area_id']);
                }
                return [];
            },
            'officers' => fn () => \Illuminate\Support\Facades\Cache::remember('inertia_on_duty_officers', 45, fn () => \App\Models\User::where('is_on_duty', true)->get(['id', 'name', 'phone', 'role'])->toArray()),
            'filters' => [
                'search' => $request->search ? (string) $request->search : '',
                'status' => $request->status ? (string) $request->status : '',
                'service_status' => $request->service_status ? (string) $request->service_status : '',
                'package_id' => $request->package_id ? (string) $request->package_id : '',
                'area_id' => $request->area_id ? (string) $request->area_id : ($request->area ? (string) $request->area : ''),
                'odp_id' => $request->odp_id ? (string) $request->odp_id : '',
                'sort_by' => $request->sort_by ? (string) $request->sort_by : '',
            ],
            'stats' => [
                'total' => ['value' => $totalCustomers, 'growth' => $totalGrowth],
                'active' => ['value' => $activeCustomers, 'percentage' => $activePercentage, 'growth' => $activeGrowth],
                'pending' => ['value' => $pendingCustomers, 'percentage' => $pendingPercentage, 'growth' => $pendingGrowth],
                'inactive' => ['value' => $inactiveCustomers, 'percentage' => $inactivePercentage, 'growth' => $inactiveGrowth],
            ],
            'statusOptions' => [
                'active' => 'Aktif',
                'pending' => 'Pending (Semua Tahap PSB)',
                'booking' => '• Tahap Booking',
                'survey' => '• Tahap Survey',
                'installing' => '• Tahap Pasang / Instalasi',
                'suspended' => 'Isolir',
                'disabled' => 'Disable Koneksi',
                'inactive' => 'Nonaktif (Semua Isolir & Disable)',
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
     * Monitoring Status Koneksi Pelanggan (Online & Offline Terpadu)
     * Dual-Source: FreeRADIUS RadAcct + MikroTik RouterOS API Live Sessions
     */
    public function connections(Request $request): Response
    {
        // 1. Ambil seluruh sesi online aktif (Dual Source: RADIUS RadAcct + Live MikroTik RouterOS API)
        $liveData = $this->getLiveConnectionSessions();
        $onlineLookup = $liveData['lookup']; // normalized key => session info array
        $onlineUsernames = $liveData['usernames']; // list of all online usernames

        // Resolve Area Filter
        $areaInput = $request->area_id ?: $request->area;
        $resolvedAreaId = null;
        if ($areaInput) {
            if (is_numeric($areaInput)) {
                $resolvedAreaId = (int) $areaInput;
            } else {
                $areaDict = DataScopeService::getAreaDictionary();
                foreach ($areaDict as $id => $name) {
                    if (mb_strtolower(trim((string)$name)) === mb_strtolower(trim((string)$areaInput))) {
                        $resolvedAreaId = (int) $id;
                        break;
                    }
                }
            }
        }

        // 2. Query Pelanggan Aktif / Suspended yang memiliki ONT (tanpa voucher)
        $customersQuery = Customer::with(['package', 'areaModel', 'ont.odp'])
            ->whereIn('status', ['active', 'suspended'])
            ->whereDoesntHave('package', fn ($q) => $q->where('access_mode', 'voucher'))
            ->whereHas('ont')
            ->when(auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->hasRole('super admin') && !auth()->user()->hasRole('superadmin') && !auth()->user()->can('customers_all_view_all'), function ($q) {
                if (auth()->user()->can('customers_all_view_area')) {
                    $q->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
                } else {
                    $q->where('sales_id', auth()->id());
                }
            })
            ->when($areaInput, fn($q) => $this->applyAreaFilter($q, $areaInput))
            ->when($request->package_id, fn ($q, $pkg) => $q->where('package_id', $pkg));

        $customers = $customersQuery->get();

        // 3. Ambil riwayat sesi offline terakhir bagi pengguna yang saat ini tidak online
        $offlineUsernames = [];
        foreach ($customers as $c) {
            $u1 = $c->ont?->pppoe_user;
            $u2 = $c->ont?->hotspot_user;

            $cIsOnline = false;
            foreach (array_filter([$u1, $u2, $c->customer_code]) as $un) {
                $k = mb_strtolower(trim((string) $un));
                $kNoRealm = explode('@', $k)[0];
                if (isset($onlineLookup[$k]) || isset($onlineLookup[$kNoRealm])) {
                    $cIsOnline = true;
                    break;
                }
            }
            if (!$cIsOnline) {
                if ($u1) {
                    $offlineUsernames[] = trim((string) $u1);
                    $offlineUsernames[] = explode('@', trim((string) $u1))[0];
                }
                if ($u2) {
                    $offlineUsernames[] = trim((string) $u2);
                    $offlineUsernames[] = explode('@', trim((string) $u2))[0];
                }
                if ($c->customer_code) {
                    $offlineUsernames[] = trim((string) $c->customer_code);
                }
            }
        }
        $offlineUsernames = array_values(array_unique(array_filter($offlineUsernames)));

        $lastOfflineSessions = collect();
        if (!empty($offlineUsernames)) {
            try {
                // FreeRADIUS table column is 'acctterminatecause' (NOT 'terminate_cause')
                $lastOfflineSessions = \App\Models\Radius\RadAcct::whereIn('username', $offlineUsernames)
                    ->whereNotNull('acctstoptime')
                    ->orderByDesc('acctstoptime')
                    ->get(['username', 'acctstoptime', 'callingstationid', 'acctterminatecause'])
                    ->unique(fn ($item) => mb_strtolower(trim(explode('@', (string) $item->username)[0])))
                    ->keyBy(fn ($item) => mb_strtolower(trim((string) $item->username)));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[CustomerController::connections] Offline radacct query error: ' . $e->getMessage());
            }
        }

        // 4. Transformasi Data Baris
        $rows = $customers->map(function (Customer $c) use ($onlineLookup, $lastOfflineSessions) {
            $mode = mb_strtolower((string) ($c->package?->access_mode ?? $c->ont?->access_mode ?? 'pppoe'));
            $isHotspot = str_contains($mode, 'hotspot');
            $primaryUsername = $isHotspot
                ? ($c->ont?->hotspot_user ?: $c->ont?->pppoe_user)
                : ($c->ont?->pppoe_user ?: $c->ont?->hotspot_user);

            // Cek sesi online dengan matching cerdas (case-insensitive & stripped @realm)
            $s = null;
            $candidates = array_filter([
                $primaryUsername,
                $c->ont?->pppoe_user,
                $c->ont?->hotspot_user,
                $c->customer_code
            ]);

            foreach ($candidates as $cand) {
                $candKey = mb_strtolower(trim((string) $cand));
                $candNoRealm = explode('@', $candKey)[0];
                if (isset($onlineLookup[$candKey])) {
                    $s = $onlineLookup[$candKey];
                    break;
                }
                if (isset($onlineLookup[$candNoRealm])) {
                    $s = $onlineLookup[$candNoRealm];
                    break;
                }
            }

            // Juga cek MAC address ONT jika ada
            if (!$s && !empty($c->ont?->mac_address)) {
                $cleanMac = mb_strtolower(preg_replace('/[^a-f0-9]/i', '', (string) $c->ont->mac_address));
                if (isset($onlineLookup['mac_' . $cleanMac])) {
                    $s = $onlineLookup['mac_' . $cleanMac];
                }
            }

            $isOnline = !is_null($s);
            $displayUsername = $primaryUsername ?: ($s['username'] ?? '-');

            $lastSession = null;
            if (!$isOnline) {
                foreach ($candidates as $cand) {
                    $candKey = mb_strtolower(trim((string) $cand));
                    $candNoRealm = explode('@', $candKey)[0];
                    if ($lastOfflineSessions->has($candKey)) {
                        $lastSession = $lastOfflineSessions->get($candKey);
                        break;
                    }
                    if ($lastOfflineSessions->has($candNoRealm)) {
                        $lastSession = $lastOfflineSessions->get($candNoRealm);
                        break;
                    }
                }
            }

            return [
                'id' => $c->id,
                'customer_code' => $c->customer_code,
                'name' => $c->name,
                'phone' => $c->phone,
                'area' => $c->areaModel?->name ?? $c->area,
                'package' => $c->package?->name,
                'package_price' => $c->package?->price,
                'username' => $displayUsername,
                'access_mode' => $c->package?->access_mode ?? $c->ont?->access_mode ?? 'pppoe',
                'is_online' => $isOnline,
                'status_connection' => $isOnline ? 'online' : 'offline',
                'ip_address' => $isOnline ? ($s['framedipaddress'] ?? null) : null,
                'mac_address' => ($s['callingstationid'] ?? null) ?: ($lastSession?->callingstationid ?: $c->ont?->mac_address),
                'nas_ip' => $s['nasipaddress'] ?? null,
                'start_time' => $s['start_time'] ?? null,
                'session_time' => $s['session_time'] ?? 0,
                'upload' => (int) ($s['upload'] ?? 0),
                'download' => (int) ($s['download'] ?? 0),
                'last_logout' => $lastSession?->acctstoptime ? (string) $lastSession->acctstoptime : null,
                'disconnect_cause' => $lastSession?->acctterminatecause ?? null,
                'customer_status' => $c->status,
                'odp_name' => $c->ont?->odp?->name,
                'ont_sn' => $c->ont?->serial_number ?: $c->ont?->sn,
            ];
        });

        // 5. Hitung Statistik Keseluruhan (Global Summary)
        $totalAll = $rows->count();
        $totalOnline = $rows->where('is_online', true)->count();
        $totalOffline = $rows->where('is_online', false)->count();
        $healthRate = $totalAll > 0 ? round(($totalOnline / $totalAll) * 100, 1) : 0;

        $pppoeCount = $rows->filter(fn($r) => str_contains(mb_strtolower($r['access_mode'] ?? ''), 'pppoe'))->count();
        $hotspotCount = $rows->filter(fn($r) => str_contains(mb_strtolower($r['access_mode'] ?? ''), 'hotspot'))->count();

        // 6. Filter Berdasarkan Tab Status: 'all', 'online', 'offline'
        $statusTab = $request->query('status');
        if (!$statusTab && $request->is('*offline*')) {
            $statusTab = 'offline';
        }
        $statusTab = $statusTab ?: 'all';

        // 7. Filter Berdasarkan Mode Akses: 'pppoe', 'hotspot'
        $rawMode = $request->query('mode') ?: $request->query('tab');
        $modeFilter = ($rawMode && $rawMode !== 'all') ? mb_strtolower($rawMode) : 'all';

        // Hitung statistik kontekstual untuk Tab Status (sesuai Mode yang dipilih)
        $modeRows = $rows;
        if ($modeFilter !== 'all') {
            $modeRows = $rows->filter(function ($r) use ($modeFilter) {
                $m = mb_strtolower($r['access_mode'] ?? '');
                return $m === $modeFilter || str_contains($m, $modeFilter);
            });
        }
        $tabTotal = $modeRows->count();
        $tabOnline = $modeRows->where('is_online', true)->count();
        $tabOffline = $modeRows->where('is_online', false)->count();

        // Hitung statistik kontekstual untuk Mode Pills (sesuai Status yang dipilih)
        $statusRows = $rows;
        if ($statusTab === 'online') {
            $statusRows = $rows->where('is_online', true);
        } elseif ($statusTab === 'offline') {
            $statusRows = $rows->where('is_online', false);
        }
        $modePppoeCount = $statusRows->filter(fn($r) => str_contains(mb_strtolower($r['access_mode'] ?? ''), 'pppoe'))->count();
        $modeHotspotCount = $statusRows->filter(fn($r) => str_contains(mb_strtolower($r['access_mode'] ?? ''), 'hotspot'))->count();

        $stats = [
            'total' => $totalAll,
            'online' => $totalOnline,
            'offline' => $totalOffline,
            'health_rate' => $healthRate,
            'pppoe_count' => $pppoeCount,
            'hotspot_count' => $hotspotCount,
            'tab_total' => $tabTotal,
            'tab_online' => $tabOnline,
            'tab_offline' => $tabOffline,
            'mode_pppoe_count' => $modePppoeCount,
            'mode_hotspot_count' => $modeHotspotCount,
        ];

        $filtered = $rows;
        if ($statusTab === 'online') {
            $filtered = $filtered->where('is_online', true);
        } elseif ($statusTab === 'offline') {
            $filtered = $filtered->where('is_online', false);
        }

        if ($modeFilter !== 'all') {
            $filtered = $filtered->filter(function ($r) use ($modeFilter) {
                $m = mb_strtolower($r['access_mode'] ?? '');
                return $m === $modeFilter || str_contains($m, $modeFilter);
            });
        }

        // 8. Filter Pencarian Text
        if ($search = trim((string) $request->search)) {
            $needle = mb_strtolower($search);
            $filtered = $filtered->filter(function ($r) use ($needle) {
                foreach (['name', 'customer_code', 'phone', 'area', 'package', 'username', 'ip_address', 'mac_address', 'ont_sn', 'odp_name'] as $f) {
                    if (!empty($r[$f]) && str_contains(mb_strtolower((string) $r[$f]), $needle)) return true;
                }
                return false;
            });
        }

        // 9. Sorting: Jika 'all', prioritaskan Online di atas, lalu abjad Nama
        $filtered = $filtered->sortBy([
            fn($a, $b) => ($b['is_online'] ? 1 : 0) <=> ($a['is_online'] ? 1 : 0),
            ['name', 'asc']
        ])->values();

        // 10. Paginasi
        $perPage = (int) ($request->per_page ?? 20);
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Customers/Online', [
            'customers' => $paginated,
            'areas' => fn () => DataScopeService::getScopedAreas($request->user(), 'customers_all'),
            'packages' => fn () => InternetPackage::where(function($q) {
                $q->whereNull('access_mode')->orWhere('access_mode', '!=', 'voucher');
            })->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $request->search,
                'area_id' => $resolvedAreaId,
                'package_id' => $request->package_id ? (int) $request->package_id : null,
                'status' => $statusTab,
                'mode' => $modeFilter ?: 'all',
            ],
            'stats' => $stats,
            'totalOnline' => $stats['online'],
            'totalOffline' => $stats['offline'],
        ]);
    }

    /**
     * Ambil seluruh sesi online aktif (Dual Source: RADIUS RadAcct + Live MikroTik RouterOS API)
     * Menggunakan cache cerdas agar beban router dan database tetap ringan & instan.
     */
    private function getLiveConnectionSessions(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('live_connection_sessions_v1', 10, function () {
            $lookup = [];
            $usernames = [];

            // 1. Baca sesi dari FreeRADIUS RadAcct
            try {
                if (class_exists(\App\Models\Radius\RadAcct::class)) {
                    $radSessions = \App\Models\Radius\RadAcct::online()
                        ->orderByDesc('acctstarttime')
                        ->get(['radacctid', 'username', 'framedipaddress', 'callingstationid', 'nasipaddress', 'acctstarttime', 'acctsessiontime', 'acctinputoctets', 'acctoutputoctets']);

                    foreach ($radSessions as $rs) {
                        $rawUser = trim((string) $rs->username);
                        if ($rawUser === '') continue;

                        $k = mb_strtolower($rawUser);
                        $kNoRealm = explode('@', $k)[0];
                        $usernames[] = $rawUser;

                        $data = [
                            'username' => $rawUser,
                            'framedipaddress' => $rs->framedipaddress,
                            'callingstationid' => $rs->callingstationid,
                            'nasipaddress' => $rs->nasipaddress,
                            'start_time' => $rs->acctstarttime ? (string) $rs->acctstarttime : null,
                            'session_time' => $rs->liveSessionTime(),
                            'upload' => (int) ($rs->acctinputoctets ?? 0),
                            'download' => (int) ($rs->acctoutputoctets ?? 0),
                            'source' => 'radius',
                        ];

                        $lookup[$k] = $data;
                        if (!isset($lookup[$kNoRealm])) {
                            $lookup[$kNoRealm] = $data;
                        }
                        if (!empty($rs->callingstationid)) {
                            $cleanMac = mb_strtolower(preg_replace('/[^a-f0-9]/i', '', $rs->callingstationid));
                            if ($cleanMac) $lookup['mac_' . $cleanMac] = $data;
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[CustomerController::connections] RADIUS query failed: ' . $e->getMessage());
            }

            // 2. Baca sesi aktif dari router MikroTik langsung (PPP & Hotspot)
            try {
                $nases = \App\Models\Radius\Nas::all();
                $ports = \App\Models\Router::whereNotNull('nas_id')->pluck('api_port', 'nas_id');
                $client = app(\App\Services\RouterOsApiClient::class);

                foreach ($nases as $nas) {
                    $host = $nas->apiHost();
                    $user = $nas->api_user;
                    $pass = (string) $nas->api_password;
                    $port = (int) ($ports->get($nas->id) ?: 8728);
                    if (!$host || !$user || !$pass) continue;

                    try {
                        $client->connect($host, $port, $user, $pass, 1.0);

                        // /ppp/active/print
                        try {
                            $pppRows = $client->command('/ppp/active/print', ['.proplist' => 'name,address,caller-id,uptime,service'])['rows'] ?? [];
                            foreach ($pppRows as $row) {
                                $uName = trim((string) ($row['name'] ?? ''));
                                if ($uName === '') continue;

                                $k = mb_strtolower($uName);
                                $kNoRealm = explode('@', $k)[0];
                                $usernames[] = $uName;

                                if (!isset($lookup[$k]) && !isset($lookup[$kNoRealm])) {
                                    $data = [
                                        'username' => $uName,
                                        'framedipaddress' => $row['address'] ?? null,
                                        'callingstationid' => $row['caller-id'] ?? null,
                                        'nasipaddress' => $host,
                                        'start_time' => null,
                                        'session_time' => $this->parseRouterUptimeSeconds($row['uptime'] ?? ''),
                                        'upload' => 0,
                                        'download' => 0,
                                        'source' => 'mikrotik_ppp',
                                    ];
                                    $lookup[$k] = $data;
                                    $lookup[$kNoRealm] = $data;
                                    if (!empty($row['caller-id'])) {
                                        $cleanMac = mb_strtolower(preg_replace('/[^a-f0-9]/i', '', $row['caller-id']));
                                        if ($cleanMac) $lookup['mac_' . $cleanMac] = $data;
                                    }
                                }
                            }
                        } catch (\Throwable $e) {}

                        // /ip/hotspot/active/print
                        try {
                            $hsRows = $client->command('/ip/hotspot/active/print', ['.proplist' => 'user,address,mac-address,uptime'])['rows'] ?? [];
                            foreach ($hsRows as $row) {
                                $uName = trim((string) ($row['user'] ?? ''));
                                if ($uName === '') continue;

                                $k = mb_strtolower($uName);
                                $kNoRealm = explode('@', $k)[0];
                                $usernames[] = $uName;

                                if (!isset($lookup[$k]) && !isset($lookup[$kNoRealm])) {
                                    $data = [
                                        'username' => $uName,
                                        'framedipaddress' => $row['address'] ?? null,
                                        'callingstationid' => $row['mac-address'] ?? null,
                                        'nasipaddress' => $host,
                                        'start_time' => null,
                                        'session_time' => $this->parseRouterUptimeSeconds($row['uptime'] ?? ''),
                                        'upload' => 0,
                                        'download' => 0,
                                        'source' => 'mikrotik_hotspot',
                                    ];
                                    $lookup[$k] = $data;
                                    $lookup[$kNoRealm] = $data;
                                    if (!empty($row['mac-address'])) {
                                        $cleanMac = mb_strtolower(preg_replace('/[^a-f0-9]/i', '', $row['mac-address']));
                                        if ($cleanMac) $lookup['mac_' . $cleanMac] = $data;
                                    }
                                }
                            }
                        } catch (\Throwable $e) {}

                    } catch (\Throwable $e) {
                    } finally {
                        $client->close();
                    }
                }
            } catch (\Throwable $e) {}

            return [
                'lookup' => $lookup,
                'usernames' => array_values(array_unique($usernames)),
            ];
        });
    }

    /**
     * Konversi format uptime MikroTik (contoh: 1w2d3h4m5s, 15m30s) menjadi detik integer
     */
    private function parseRouterUptimeSeconds(?string $str): int
    {
        if (!$str) return 0;
        $seconds = 0;
        if (preg_match('/(?:(\d+)w)?(?:(\d+)d)?(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?/', $str, $m)) {
            $weeks = (int) ($m[1] ?? 0);
            $days  = (int) ($m[2] ?? 0);
            $hours = (int) ($m[3] ?? 0);
            $mins  = (int) ($m[4] ?? 0);
            $secs  = (int) ($m[5] ?? 0);
            $seconds = ($weeks * 604800) + ($days * 86400) + ($hours * 3600) + ($mins * 60) + $secs;
        }
        return $seconds;
    }

    public function online(Request $request): Response
    {
        return $this->connections($request);
    }

    public function offline(Request $request): Response
    {
        return $this->connections($request);
    }

    /**
     * Menu Khusus Data Pelanggan Berbayar Lengkap dengan Status Pembayaran & Tagihan
     */
    public function paid(Request $request): Response
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->can('menu_customers_all') && !$user->can('menu_invoices')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka Menu Pelanggan Berbayar.');
        }

        // Tentukan Periode Bulan & Tahun (Default: Bulan & Tahun Berjalan)
        $selectedMonth = (int) ($request->period_month ?: now()->month);
        $selectedYear = (int) ($request->period_year ?: now()->year);

        // Base Query Pelanggan Berbayar (Bukan Gratis & Bukan Free Home, Tidak Terminated)
        $baseQuery = Customer::query()
            ->where(function($q) {
                $q->whereNull('service_status')
                  ->orWhereNotIn('service_status', ['gratis', 'free_home']);
            })
            ->where('status', '!=', 'terminated')
            ->when(auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_all_view_all'), function($q) {
                if (auth()->user()->can('customers_all_view_area')) {
                    $q->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
                } else {
                    $q->where('sales_id', auth()->id());
                }
            })
            ->when($request->filled('area_id'), function ($q) use ($request) {
                $areaId = $request->area_id;
                $q->where(function ($sub) use ($areaId) {
                    $sub->where('area_id', $areaId);
                    $areaName = \App\Models\Area::where('id', $areaId)->value('name');
                    if ($areaName) $sub->orWhere('area', $areaName);
                });
            })
            ->when($request->filled('package_id'), fn ($q) => $q->where('package_id', $request->package_id));

        // 1. Online Usernames & Sessions dari FreeRADIUS
        $onlineSessions = collect();
        $onlineUsernames = [];
        try {
            $onlineSessions = \App\Models\Radius\RadAcct::online()
                ->orderByDesc('acctstarttime')
                ->get()
                ->unique('username')
                ->keyBy('username');
            $onlineUsernames = $onlineSessions->keys()->all();
        } catch (\Exception $e) {
            $onlineSessions = collect();
            $onlineUsernames = [];
        }

        // 2. Statistik Global untuk Pelanggan Berbayar
        $totalPaidCustomers = (clone $baseQuery)->count();
        $totalMonthlyPotential = (clone $baseQuery)->with('package')->get()->sum(fn($c) => $c->package?->price ?? 0);

        // Ambil ID semua pelanggan berbayar untuk kalkulasi agregat invoice
        $customerIds = (clone $baseQuery)->pluck('id')->toArray();

        // Ambil invoice untuk periode terpilih
        $periodInvoices = \App\Models\Invoice::whereIn('customer_id', $customerIds)
            ->where('period_month', $selectedMonth)
            ->where('period_year', $selectedYear)
            ->where('is_reseller_balance', false)
            ->with('payments')
            ->get()
            ->groupBy('customer_id');

        // Ambil tunggakan masa lalu (invoice sebelum periode terpilih yang belum paid)
        $pastUnpaidInvoices = \App\Models\Invoice::whereIn('customer_id', $customerIds)
            ->where('status', '!=', 'paid')
            ->where('is_reseller_balance', false)
            ->where(function($q) use ($selectedMonth, $selectedYear) {
                $q->where('period_year', '<', $selectedYear)
                  ->orWhere(function($subQ) use ($selectedMonth, $selectedYear) {
                      $subQ->where('period_year', $selectedYear)
                           ->where('period_month', '<', $selectedMonth);
                  });
            })
            ->get()
            ->groupBy('customer_id');

        // Hitung statistik pembayaran periode ini
        $paidCount = 0;
        $paidAmount = 0;
        $unpaidCount = 0;
        $unpaidAmount = 0;
        $overdueCount = 0;
        $overdueAmount = 0;
        $partialCount = 0;
        $noInvoiceCount = 0;
        $suspendedCount = (clone $baseQuery)->where('status', 'suspended')->count();

        $today = now()->startOfDay();

        foreach ($customerIds as $cId) {
            $inv = $periodInvoices->get($cId)?->first();
            if ($inv) {
                if ($inv->status === 'paid') {
                    $paidCount++;
                    $paidAmount += $inv->amount;
                } elseif ($inv->status === 'partial') {
                    $partialCount++;
                    $paidAmount += $inv->total_paid;
                    $unpaidAmount += $inv->remaining;
                } else {
                    $isOverdue = $inv->due_date && \Carbon\Carbon::parse($inv->due_date)->startOfDay()->lt($today);
                    if ($isOverdue) {
                        $overdueCount++;
                        $overdueAmount += $inv->amount;
                    } else {
                        $unpaidCount++;
                        $unpaidAmount += $inv->amount;
                    }
                }
            } else {
                $noInvoiceCount++;
            }
        }

        // Online count
        $onlineCount = (clone $baseQuery)->whereHas('ont', fn($q) => $q->whereIn('pppoe_user', $onlineUsernames))->count();
        $offlineCount = max(0, $totalPaidCustomers - $onlineCount);

        $stats = [
            'total' => $totalPaidCustomers,
            'total_potential' => $totalMonthlyPotential,
            'paid_count' => $paidCount,
            'paid_amount' => $paidAmount,
            'unpaid_count' => $unpaidCount,
            'unpaid_amount' => $unpaidAmount,
            'overdue_count' => $overdueCount,
            'overdue_amount' => $overdueAmount,
            'partial_count' => $partialCount,
            'no_invoice_count' => $noInvoiceCount,
            'suspended_count' => $suspendedCount,
            'online_count' => $onlineCount,
            'offline_count' => $offlineCount,
            'collection_rate' => $totalPaidCustomers > 0 ? round(($paidCount / $totalPaidCustomers) * 100, 1) : 0,
        ];

        // 3. Query Data Pelanggan Berbayar dengan Filter & Pagination
        $query = (clone $baseQuery)->with(['package', 'areaModel', 'ont.odp', 'user', 'sales']);

        // Filter Koneksi Online / Offline
        if ($request->filled('connection_status') && in_array($request->connection_status, ['online', 'offline'])) {
            if ($request->connection_status === 'online') {
                $query->whereHas('ont', fn($oq) => $oq->whereIn('pppoe_user', $onlineUsernames));
            } elseif ($request->connection_status === 'offline') {
                $query->where(function($sq) use ($onlineUsernames) {
                    $sq->whereDoesntHave('ont')
                       ->orWhereHas('ont', fn($oq) => $oq->whereNotIn('pppoe_user', $onlineUsernames)->orWhereNull('pppoe_user'));
                });
            }
        }

        // Filter Pencarian Teks Cerdas
        if ($search = trim((string) $request->search)) {
            $query->where(function($sq) use ($search) {
                $sq->where('name', 'like', "%{$search}%")
                   ->orWhere('customer_code', 'like', "%{$search}%")
                   ->orWhere('phone', 'like', "%{$search}%")
                   ->orWhere('address', 'like', "%{$search}%")
                   ->orWhereHas('ont', fn($oq) => $oq->where('pppoe_user', 'like', "%{$search}%")->orWhere('hotspot_user', 'like', "%{$search}%"))
                   ->orWhereHas('invoices', fn($iq) => $iq->where('invoice_number', 'like', "%{$search}%"));
            });
        }

        // Filter Status Pembayaran
        if ($request->filled('payment_status')) {
            $pStatus = $request->payment_status;
            if ($pStatus === 'paid') {
                $query->whereHas('invoices', function($iq) use ($selectedMonth, $selectedYear) {
                    $iq->where('period_month', $selectedMonth)
                       ->where('period_year', $selectedYear)
                       ->where('status', 'paid')
                       ->where('is_reseller_balance', false);
                });
            } elseif ($pStatus === 'unpaid') {
                $query->whereHas('invoices', function($iq) use ($selectedMonth, $selectedYear, $today) {
                    $iq->where('period_month', $selectedMonth)
                       ->where('period_year', $selectedYear)
                       ->where('status', 'unpaid')
                       ->where(function($sq) use ($today) {
                           $sq->whereNull('due_date')->orWhere('due_date', '>=', $today);
                       })
                       ->where('is_reseller_balance', false);
                });
            } elseif ($pStatus === 'overdue') {
                $query->where(function($qOverdue) use ($selectedMonth, $selectedYear, $today) {
                    $qOverdue->whereHas('invoices', function($iq) use ($selectedMonth, $selectedYear, $today) {
                        $iq->where('period_month', $selectedMonth)
                           ->where('period_year', $selectedYear)
                           ->where('status', '!=', 'paid')
                           ->whereNotNull('due_date')
                           ->where('due_date', '<', $today)
                           ->where('is_reseller_balance', false);
                    })->orWhereHas('invoices', function($iqPast) use ($selectedMonth, $selectedYear) {
                        $iqPast->where('status', '!=', 'paid')
                               ->where('is_reseller_balance', false)
                               ->where(function($subQ) use ($selectedMonth, $selectedYear) {
                                   $subQ->where('period_year', '<', $selectedYear)
                                        ->orWhere(function($mQ) use ($selectedMonth, $selectedYear) {
                                            $mQ->where('period_year', $selectedYear)
                                               ->where('period_month', '<', $selectedMonth);
                                        });
                               });
                    });
                });
            } elseif ($pStatus === 'isolir') {
                $query->where('status', 'suspended');
            } elseif ($pStatus === 'partial') {
                $query->whereHas('invoices', function($iq) use ($selectedMonth, $selectedYear) {
                    $iq->where('period_month', $selectedMonth)
                       ->where('period_year', $selectedYear)
                       ->where('status', 'partial')
                       ->where('is_reseller_balance', false);
                });
            } elseif ($pStatus === 'no_invoice') {
                $query->whereDoesntHave('invoices', function($iq) use ($selectedMonth, $selectedYear) {
                    $iq->where('period_month', $selectedMonth)
                       ->where('period_year', $selectedYear)
                       ->where('is_reseller_balance', false);
                });
            }
        }

        $perPage = (int) ($request->per_page ?? 15);
        $customers = $query->latest('id')->paginate($perPage)->withQueryString();

        // 4. Enrich Customer Collection dengan Data Pembayaran & Sesi Real-Time
        $customers->getCollection()->transform(function ($c) use ($periodInvoices, $pastUnpaidInvoices, $onlineSessions, $today) {
            $inv = $periodInvoices->get($c->id)?->first();
            $pastInvs = $pastUnpaidInvoices->get($c->id) ?: collect();

            // Sesi online FreeRADIUS
            $pppoeUser = $c->ont?->pppoe_user;
            $hotspotUser = $c->ont?->hotspot_user;
            $session = ($pppoeUser ? $onlineSessions->get($pppoeUser) : null) ?: ($hotspotUser ? $onlineSessions->get($hotspotUser) : null);

            $c->is_online = !is_null($session);
            $c->online_session = $session ? [
                'ip' => $session->framedipaddress ?? '-',
                'mac' => $session->callingstationid ?? '-',
                'uptime' => $session->liveSessionTime(),
                'nas_ip' => $session->nasipaddress ?? '-',
            ] : null;

            // Tunggakan masa lalu
            $pastDebtAmount = $pastInvs->sum(fn($i) => $i->status === 'partial' ? $i->remaining : $i->amount);
            $pastDebtMonths = $pastInvs->count();

            // Status pembayaran cerdas untuk periode ini
            $paymentStatus = 'no_invoice';
            $isOverdue = false;
            $daysOverdue = 0;

            if ($inv) {
                if ($inv->status === 'paid') {
                    $paymentStatus = 'paid';
                } elseif ($inv->status === 'partial') {
                    $paymentStatus = 'partial';
                } else {
                    if ($inv->due_date && \Carbon\Carbon::parse($inv->due_date)->startOfDay()->lt($today)) {
                        $paymentStatus = 'overdue';
                        $isOverdue = true;
                        $daysOverdue = (int) \Carbon\Carbon::parse($inv->due_date)->startOfDay()->diffInDays($today);
                    } else {
                        $paymentStatus = 'unpaid';
                    }
                }
            }

            $c->is_suspended = ($c->status === 'suspended');

            $c->current_invoice = $inv ? [
                'id' => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'amount' => (int) $inv->amount,
                'total_paid' => (int) $inv->total_paid,
                'remaining' => (int) $inv->remaining,
                'due_date' => $inv->due_date ? $inv->due_date->format('Y-m-d') : null,
                'status' => $inv->status,
                'payment_channel' => $inv->payment_channel,
                'paid_date' => $inv->payments->where('status', 'verified')->max('payment_date'),
            ] : null;

            $c->payment_summary = [
                'status' => $paymentStatus,
                'is_overdue' => $isOverdue || ($pastDebtMonths > 0),
                'days_overdue' => $daysOverdue,
                'past_debt_amount' => (int) $pastDebtAmount,
                'past_debt_months' => $pastDebtMonths,
                'total_bill' => (int) (($inv ? ($inv->status === 'partial' ? $inv->remaining : ($inv->status === 'paid' ? 0 : $inv->amount)) : ($c->package?->price ?? 0)) + $pastDebtAmount),
            ];

            return $c;
        });

        return Inertia::render('Customers/Paid', [
            'customers' => $customers,
            'packages' => InternetPackage::active()->get(['id', 'name', 'speed_mbps', 'price', 'access_mode']),
            'areas' => DataScopeService::getScopedAreas($request->user(), 'customers_all'),
            'filters' => [
                'search' => $request->search ?: '',
                'area_id' => $request->area_id ?: '',
                'package_id' => $request->package_id ?: '',
                'payment_status' => $request->payment_status ?: '',
                'connection_status' => $request->connection_status ?: '',
                'period_month' => $selectedMonth,
                'period_year' => $selectedYear,
                'per_page' => $perPage,
            ],
            'stats' => $stats,
            'currentPeriod' => [
                'month' => $selectedMonth,
                'year' => $selectedYear,
                'label' => (['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][$selectedMonth] ?? '') . ' ' . $selectedYear,
            ],
        ]);
    }

    /**
     * Menu Khusus Data Pelanggan Gratis (Bebas Tagihan / Non-Invoice)
     */
    public function gratis(Request $request): Response
    {
        $baseQuery = Customer::query()
            ->where('service_status', 'gratis')
            ->where('status', '!=', 'terminated')
            ->when(auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_all_view_all'), function($q) {
                if (auth()->user()->can('customers_all_view_area')) {
                    $q->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
                } else {
                    $q->where('sales_id', auth()->id());
                }
            });

        // Online sessions dari RADIUS
        $onlineUsernames = (function() {
            try {
                return \App\Models\Radius\RadAcct::online()->pluck('username')->toArray();
            } catch (\Exception $e) {
                return [];
            }
        })();

        // Filtered base query (sebelum connection_status dan status spesifik agar card & pill stats sinkron dengan filter pencarian / wilayah / paket)
        $filteredBaseQuery = (clone $baseQuery)
            ->when($request->filled('search'), function($q) use ($request) {
                $search = trim($request->search);
                $q->where(function($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                       ->orWhere('customer_code', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%")
                       ->orWhere('address', 'like', "%{$search}%")
                       ->orWhereHas('ont', fn($oq) => $oq->where('pppoe_user', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('area_id') || $request->filled('area'), function ($q) use ($request) {
                $this->applyAreaFilter($q, $request->area_id ?: $request->area);
            })
            ->when($request->filled('package_id'), fn($q) => $q->where('package_id', $request->package_id))
            ->when($request->filled('access_mode') && $request->access_mode !== '', function($q) use ($request) {
                $mode = strtolower(trim($request->access_mode));
                $q->where(function($sq) use ($mode) {
                    $sq->whereHas('package', fn($pq) => $pq->where('access_mode', $mode))
                       ->orWhereHas('ont', fn($oq) => $oq->where('access_mode', $mode));
                });
            });

        // Statistik Ringkasan Cerdas (mengikuti filter yang sedang aktif)
        $totalGratis = (clone $filteredBaseQuery)->count();
        $activeGratis = (clone $filteredBaseQuery)->where('status', 'active')->count();
        $totalBandwidthMbps = (clone $filteredBaseQuery)->with('package')->get()->sum(fn($c) => $c->package?->speed_mbps ?? 0);
        $totalCsrValue = (clone $filteredBaseQuery)->with('package')->get()->sum(fn($c) => $c->package?->price ?? 0);
        $onlineGratis = !empty($onlineUsernames) ? (clone $filteredBaseQuery)->whereHas('ont', fn($q) => $q->whereIn('pppoe_user', $onlineUsernames))->count() : 0;
        $offlineGratis = max(0, $totalGratis - $onlineGratis);

        // Data Pelanggan Gratis dengan filter lanjutan (connection_status & status) & pagination
        $customers = (clone $filteredBaseQuery)
            ->with(['package', 'ont.odp', 'areaModel', 'user', 'sales'])
            ->when($request->filled('connection_status') && in_array($request->connection_status, ['online', 'offline']), function($q) use ($request, $onlineUsernames) {
                if ($request->connection_status === 'online') {
                    if (empty($onlineUsernames)) {
                        $q->whereRaw('0 = 1');
                    } else {
                        $q->whereHas('ont', fn($oq) => $oq->whereIn('pppoe_user', $onlineUsernames));
                    }
                } elseif ($request->connection_status === 'offline') {
                    if (!empty($onlineUsernames)) {
                        $q->where(function($sq) use ($onlineUsernames) {
                            $sq->whereDoesntHave('ont')
                               ->orWhereHas('ont', fn($oq) => $oq->whereNotIn('pppoe_user', $onlineUsernames)->orWhereNull('pppoe_user'));
                        });
                    }
                }
            })
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->latest('id')
            ->paginate($request->per_page ?? 12)
            ->withQueryString();

        return Inertia::render('Customers/Gratis', [
            'customers' => $customers,
            'packages' => InternetPackage::active()->get(['id', 'name', 'speed_mbps', 'price', 'access_mode']),
            'areas' => DataScopeService::getScopedAreas($request->user(), 'customers_all'),
            'filters' => [
                'search' => $request->search ?? '',
                'area_id' => $request->area_id ?? ($request->area ?? ''),
                'package_id' => $request->package_id ?? '',
                'status' => $request->status ?? '',
                'connection_status' => $request->connection_status ?? '',
                'access_mode' => $request->access_mode ?? '',
            ],
            'onlineUsernames' => $onlineUsernames,
            'stats' => [
                'total' => $totalGratis,
                'active' => $activeGratis,
                'online' => $onlineGratis,
                'offline' => $offlineGratis,
                'total_bandwidth' => $totalBandwidthMbps,
                'monthly_csr_value' => $totalCsrValue,
            ],
        ]);
    }

    /**
     * Menu Khusus Data Pelanggan Free Home (Voucher Rumahan ONT)
     */
    public function freeHome(Request $request): Response
    {
        $baseQuery = Customer::query()
            ->where('service_status', 'free_home')
            ->where('status', '!=', 'terminated')
            ->when(auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_all_view_all'), function($q) {
                if (auth()->user()->can('customers_all_view_area')) {
                    $q->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
                } else {
                    $q->where('sales_id', auth()->id());
                }
            });

        // Online sessions dari RADIUS (jika ada mac/ip voucher aktif atau pppoe)
        $onlineUsernames = (function() {
            try {
                return \App\Models\Radius\RadAcct::online()->pluck('username')->toArray();
            } catch (\Exception $e) {
                return [];
            }
        })();

        // Filtered base query (mengikuti pencarian dan wilayah)
        $filteredBaseQuery = (clone $baseQuery)
            ->when($request->filled('search'), function($q) use ($request) {
                $search = trim($request->search);
                $q->where(function($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                       ->orWhere('customer_code', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%")
                       ->orWhere('address', 'like', "%{$search}%")
                       ->orWhereHas('ont', fn($oq) => $oq->where('serial_number', 'like', "%{$search}%")->orWhere('model', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('area_id') || $request->filled('area'), function ($q) use ($request) {
                $this->applyAreaFilter($q, $request->area_id ?: $request->area);
            });

        // Statistik Ringkasan Cerdas Free Home (mengikuti area & search yang dipilih)
        $totalFreeHome = (clone $filteredBaseQuery)->count();
        $activeFreeHome = (clone $filteredBaseQuery)->where('status', 'active')->count();
        $installedOnt = (clone $filteredBaseQuery)->has('ont')->count();
        $withOdp = (clone $filteredBaseQuery)->whereHas('ont', fn($q) => $q->whereNotNull('odp_id'))->count();
        $onlineFreeHome = !empty($onlineUsernames) ? (clone $filteredBaseQuery)->whereHas('ont', fn($q) => $q->whereIn('pppoe_user', $onlineUsernames)->orWhereIn('hotspot_user', $onlineUsernames))->count() : 0;

        // Data Pelanggan Free Home dengan filter lanjutan (status, has_ont, has_odp) & pagination
        $customers = (clone $filteredBaseQuery)
            ->with(['ont.odp.odc.olt', 'areaModel', 'user', 'sales'])
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->when($request->filled('has_ont'), function ($q) use ($request) {
                if ($request->has_ont === 'yes') {
                    $q->has('ont');
                } else {
                    $q->doesntHave('ont');
                }
            })
            ->when($request->filled('has_odp'), function ($q) use ($request) {
                if ($request->has_odp === 'yes') {
                    $q->whereHas('ont', fn($oq) => $oq->whereNotNull('odp_id'));
                } else {
                    $q->where(function ($sub) {
                        $sub->doesntHave('ont')->orWhereHas('ont', fn($oq) => $oq->whereNull('odp_id'));
                    });
                }
            })
            ->latest('id')
            ->paginate($request->per_page ?? 12)
            ->withQueryString();

        return Inertia::render('Customers/FreeHome', [
            'customers' => $customers,
            'packages' => InternetPackage::active()->get(['id', 'name', 'speed_mbps', 'price']),
            'areas' => DataScopeService::getScopedAreas($request->user(), 'customers_all'),
            'filters' => $request->only(['search', 'area_id', 'status', 'has_ont', 'has_odp']),
            'onlineUsernames' => $onlineUsernames,
            'stats' => [
                'total' => $totalFreeHome,
                'active' => $activeFreeHome,
                'installed_ont' => $installedOnt,
                'with_odp' => $withOdp,
                'online' => $onlineFreeHome,
            ],
        ]);
    }

    /**
     * Beralih Status Layanan (Berbayar <-> Gratis <-> Free Home)
     */
    public function toggleServiceStatus(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'service_status' => 'required|in:berbayar,gratis,free_home',
            'package_id' => 'nullable|exists:internet_packages,id',
            'base_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $oldStatus = $customer->service_status;
        $newStatus = $validated['service_status'];

        DB::transaction(function() use ($customer, $newStatus, $validated, $oldStatus) {
            $updateData = [
                'service_status' => $newStatus,
            ];

            if ($newStatus === 'gratis') {
                $updateData['base_amount'] = 0;
                // Bersihkan tagihan berjalan yang belum lunas jika diubah ke gratis
                \App\Models\Invoice::where('customer_id', $customer->id)
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->delete();
            } elseif ($newStatus === 'free_home') {
                $updateData['base_amount'] = 0;
                $updateData['package_id'] = null;
                // Bersihkan tagihan berjalan yang belum lunas jika diubah ke free home
                \App\Models\Invoice::where('customer_id', $customer->id)
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->delete();
            } else {
                // Berbayar: jika ada paket dipilih
                if (!empty($validated['package_id'])) {
                    $pkg = InternetPackage::find($validated['package_id']);
                    $updateData['package_id'] = $pkg?->id;
                    $updateData['base_amount'] = !empty($validated['base_amount']) && $validated['base_amount'] > 0 
                        ? $validated['base_amount'] 
                        : ($pkg?->price ?? 0);
                } else {
                    $updateData['base_amount'] = !empty($validated['base_amount']) && $validated['base_amount'] > 0 
                        ? $validated['base_amount'] 
                        : ($customer->package?->price ?? 0);
                }
            }

            if (!empty($validated['notes'])) {
                $statusLabel = match($newStatus) {
                    'free_home' => 'Free Home (Voucher Rumahan ONT)',
                    'gratis' => 'Gratis (Non-Invoice)',
                    default => 'Berbayar'
                };
                $updateData['notes'] = ($customer->notes ? $customer->notes . "\n" : '') . "[" . now()->format('d/m/Y H:i') . "] Status layanan diubah ke {$statusLabel}: " . $validated['notes'];
            }

            $customer->update($updateData);

            \App\Models\AuditLog::createLog('Update Service Status', $customer, ['service_status' => $oldStatus], ['service_status' => $newStatus], 'Mengubah status layanan ke ' . ucfirst($newStatus));
        });

        $msg = match($newStatus) {
            'free_home' => "Pelanggan {$customer->name} berhasil diubah menjadi Layanan Free Home (Voucher Rumahan ONT).",
            'gratis' => "Pelanggan {$customer->name} berhasil diubah menjadi Pelanggan Gratis (Bebas Tagihan).",
            default => "Pelanggan {$customer->name} berhasil diubah menjadi Pelanggan Berbayar."
        };

        return back()->with('success', $msg);
    }

    /**
     * Halaman Data Booking (status = booking)
     */
    public function booking(Request $request): Response
    {
        $query = Customer::booking()
            ->when(auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_booking_view_all'), function($q) {
                if (auth()->user()->can('customers_booking_view_area')) {
                    $accessibleAreas = auth()->user()->getAccessibleAreaIds();
                    $q->where(function($sub) use ($accessibleAreas) {
                        $sub->whereIn('area_id', $accessibleAreas)
                            ->orWhere('sales_id', auth()->id())
                            ->orWhereHas('technicianSchedules', fn($ts) => $ts->where('technician_id', auth()->id()))
                            ->orWhereHas('surveys', fn($sq) => $sq->where('surveyor_id', auth()->id()));
                    });
                } else {
                    $q->where(function($sub) {
                        $sub->where('sales_id', auth()->id())
                            ->orWhereHas('technicianSchedules', fn($ts) => $ts->where('technician_id', auth()->id()))
                            ->orWhereHas('surveys', fn($sq) => $sq->where('surveyor_id', auth()->id()));
                    });
                }
            })
            ->when($request->date, function ($q, $date) {
                $q->where(function ($sub) use ($date) {
                    $sub->whereDate('registration_date', $date)
                        ->orWhereDate('created_at', $date);
                });
            })
            ->when($request->area || $request->area_id, fn($q) => $this->applyAreaFilter($q, $request->area_id ?: $request->area))
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->search($request->search);

        $customers = (clone $query)
            ->with(['package', 'sales.employee'])
            ->orderByRaw('COALESCE(registration_date, created_at) DESC, id DESC')
            ->paginate(15)
            ->withQueryString();

        // Calculate statistics based on the current filtered view (1x query agregasi)
        $bAgg = (clone $query)->selectRaw("
            COUNT(*) as total_all,
            COALESCE(SUM(CASE WHEN status = 'booking' THEN 1 ELSE 0 END), 0) as baru_all,
            COALESCE(SUM(CASE WHEN status = 'survey' THEN 1 ELSE 0 END), 0) as survey_all
        ")->first();
        $stats = [
            'total' => (int) ($bAgg->total_all ?? 0),
            'baru' => (int) ($bAgg->baru_all ?? 0),
            'disurvey' => (int) ($bAgg->survey_all ?? 0),
        ];

        $resolveCsOfficers = function () {
            static $memo = null;
            if ($memo !== null) return $memo;
            $today = now()->toDateString();
            $currentTime = now()->format('H:i');

            $csOfficers = User::where(function ($q) {
                    $q->where('role', 'cs')
                        ->orWhere('role', 'like', '%cs%')
                        ->orWhere('role', 'like', '%customer%')
                        ->orWhereHas('roles', fn ($rq) => $rq->where('name', 'like', '%cs%'))
                        ->orWhereHas('employee', function ($eq) {
                            $eq->where('position', 'like', '%cs%')
                                ->orWhere('position', 'like', '%customer%');
                        });
                })
                ->where(function ($q) {
                    $q->where('is_active', true)->orWhereNull('is_active');
                })
                ->with(['employee.schedules' => function ($sq) use ($today) {
                    $sq->whereDate('date', $today)->with('shift');
                }])
                ->get(['id', 'name', 'phone', 'email', 'role', 'is_on_duty'])
                ->map(function ($u) use ($currentTime) {
                    $todaySched = $u->employee?->schedules?->first();
                    $shift = $todaySched?->shift;
                    $isOff = !$todaySched || in_array(strtolower((string) $todaySched->status), ['off', 'libur', 'cuti', 'izin', 'sakit']) || ($shift && (stripos($shift->name, 'libur') !== false || stripos($shift->name, 'off') !== false));

                    $isCurrentShift = false;
                    if ($shift && !$isOff && $shift->start_time && $shift->end_time) {
                        $start = substr($shift->start_time, 0, 5);
                        $end = substr($shift->end_time, 0, 5);
                        if ($start <= $end) {
                            $isCurrentShift = ($currentTime >= $start && $currentTime <= $end);
                        } else {
                            $isCurrentShift = ($currentTime >= $start || $currentTime <= $end);
                        }
                    }

                    $phone = $u->phone ?: ($u->employee?->phone ?: '');

                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'phone' => $phone,
                        'email' => $u->email,
                        'role' => $u->employee?->position ?: ($u->role ?: 'CS'),
                        'is_on_duty' => (bool) ($u->is_on_duty || (!$isOff && $todaySched)),
                        'is_scheduled_today' => (bool) (!$isOff && $todaySched),
                        'is_current_shift' => (bool) $isCurrentShift,
                        'shift_name' => $shift?->name ?: ($isOff ? 'Libur / Off' : '-'),
                        'shift_time' => $shift?->formatted_time ?: '',
                        'schedule_status' => $todaySched?->status ?: 'off',
                    ];
                })
                ->filter(fn ($item) => !empty($item['phone']))
                ->values();

            if ($csOfficers->isEmpty()) {
                $csOfficers = User::where(function ($q) {
                        $q->where('role', 'cs')
                            ->orWhere('role', 'like', '%cs%')
                            ->orWhere('role', 'admin');
                    })
                    ->where(function ($q) {
                        $q->where('is_active', true)->orWhereNull('is_active');
                    })
                    ->get(['id', 'name', 'phone', 'email', 'role', 'is_on_duty'])
                    ->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'phone' => $u->phone ?: '',
                        'email' => $u->email,
                        'role' => $u->role ?: 'CS',
                        'is_on_duty' => (bool) $u->is_on_duty,
                        'is_scheduled_today' => false,
                        'is_current_shift' => false,
                        'shift_name' => '-',
                        'shift_time' => '',
                        'schedule_status' => 'standby',
                    ])
                    ->filter(fn ($item) => !empty($item['phone']))
                    ->values();
            }

            return $memo = $csOfficers;
        };

        return Inertia::render('Customers/Booking', [
            'customers' => $customers,
            'areas' => fn () => \App\Models\Area::orderBy('name')->pluck('name')->all(),
            'stats' => $stats,
            'technicians' => fn () => $resolveCsOfficers(),
            'cs_officers' => fn () => $resolveCsOfficers(),
            'filters' => $request->only(['search', 'date', 'area', 'area_id', 'status']),
        ]);
    }

    public function canceled(Request $request): Response
    {
        $query = Customer::where('status', 'canceled')
            ->when(auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_booking_view_all'), function($q) {
                if (auth()->user()->can('customers_booking_view_area')) {
                    $accessibleAreas = auth()->user()->getAccessibleAreaIds();
                    $q->where(function($sub) use ($accessibleAreas) {
                        $sub->whereIn('area_id', $accessibleAreas)
                            ->orWhere('sales_id', auth()->id())
                            ->orWhereHas('technicianSchedules', fn($ts) => $ts->where('technician_id', auth()->id()))
                            ->orWhereHas('surveys', fn($sq) => $sq->where('surveyor_id', auth()->id()));
                    });
                } else {
                    $q->where(function($sub) {
                        $sub->where('sales_id', auth()->id())
                            ->orWhereHas('technicianSchedules', fn($ts) => $ts->where('technician_id', auth()->id()))
                            ->orWhereHas('surveys', fn($sq) => $sq->where('surveyor_id', auth()->id()));
                    });
                }
            })
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            });

        $customers = (clone $query)
            ->with(['package', 'sales', 'areaModel', 'user', 'surveys.odp'])
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
            ->with(['package', 'surveys.odp', 'surveys.surveyor', 'technicianSchedules' => function ($q) {
                $q->where('type', 'survey')->with('technician');
            }])
            ->when(auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_survey_view_all'), function($q) {
                if (auth()->user()->can('customers_survey_view_area')) {
                    $accessibleAreas = auth()->user()->getAccessibleAreaIds();
                    $q->where(function($sub) use ($accessibleAreas) {
                        $sub->whereIn('area_id', $accessibleAreas)
                            ->orWhereHas('technicianSchedules', function($sq) {
                                $sq->where('technician_id', auth()->id())->where('type', 'survey');
                            })
                            ->orWhereHas('surveys', function($sq) {
                                $sq->where('surveyor_id', auth()->id());
                            })
                            ->orWhere('sales_id', auth()->id());
                    });
                } elseif (auth()->user()->can('customers_survey_view_assigned') || auth()->user()->hasRole('teknisi')) {
                    $q->where(function($sub) {
                        $sub->whereHas('technicianSchedules', function($sq) {
                            $sq->where('technician_id', auth()->id())->where('type', 'survey');
                        })
                        ->orWhereHas('surveys', function($sq) {
                            $sq->where('surveyor_id', auth()->id());
                        });
                    });
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
                $q->where(function($sub) use ($date) {
                    $sub->whereDate('created_at', $date)
                        ->orWhereDate('registration_date', $date)
                        ->orWhereHas('technicianSchedules', function($sq) use ($date) {
                            $sq->whereDate('scheduled_date', $date)->where('type', 'survey');
                        })
                        ->orWhereHas('surveys', function($sq) use ($date) {
                            $sq->whereDate('created_at', $date);
                        });
                });
            })
            ->when($request->area || $request->area_id, fn($q) => $this->applyAreaFilter($q, $request->area_id ?: $request->area))
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
            ->orderByRaw('COALESCE(registration_date, created_at) DESC, id DESC')
            ->paginate(15)
            ->withQueryString();

        // Auto-heal ghost ports dibatasi maksimal 1x per 2 menit agar klik filter tetap instan
        if (\Illuminate\Support\Facades\Cache::add('survey_odp_ports_autoheal_lock', 1, 120)) {
            try {
                $hasReserved = \App\Models\OdpPort::where('status', 'reserved')->exists();
                if ($hasReserved) {
                    $ghostReservedIds = \App\Models\OdpPort::where('status', 'reserved')
                        ->whereNotExists(function ($query) {
                            $query->select(\Illuminate\Support\Facades\DB::raw(1))
                                ->from('surveys')
                                ->join('customers', 'surveys.customer_id', '=', 'customers.id')
                                ->whereColumn('surveys.odp_id', 'odp_ports.odp_id')
                                ->whereColumn('surveys.port_number', 'odp_ports.port_number')
                                ->whereIn('customers.status', ['survey', 'installing']);
                        })
                        ->pluck('id');

                    if ($ghostReservedIds->isNotEmpty()) {
                        \App\Models\OdpPort::whereIn('id', $ghostReservedIds)->update(['status' => 'available']);
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[CustomerController::surveyIndex] Auto-heal reserved ports skipped: ' . $e->getMessage());
            }

            try {
                $ghostUsedIds = \App\Models\OdpPort::where('status', 'used')
                    ->whereNotExists(function ($query) {
                        $query->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('onts')
                            ->whereColumn('onts.odp_id', 'odp_ports.odp_id')
                            ->whereColumn('onts.port_number', 'odp_ports.port_number')
                            ->whereNotNull('onts.customer_id')
                            ->where('onts.status', '!=', 'inactive');
                    })
                    ->limit(20)
                    ->pluck('id');

                if ($ghostUsedIds->isNotEmpty()) {
                    \App\Models\OdpPort::whereIn('id', $ghostUsedIds)->update(['status' => 'available']);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[CustomerController::surveyIndex] Auto-heal used ports skipped: ' . $e->getMessage());
            }
        }

        $openCustomer = null;
        if ($request->filled('open_report')) {
            $openCustomerQuery = Customer::with(['package', 'surveys.odp', 'surveys.surveyor', 'technicianSchedules' => function ($q) {
                $q->where('type', 'survey')->with('technician');
            }]);
            if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_survey_view_all')) {
                if (auth()->user()->can('customers_survey_view_area')) {
                    $accessibleAreas = auth()->user()->getAccessibleAreaIds();
                    $openCustomerQuery->where(function($sub) use ($accessibleAreas) {
                        $sub->whereIn('area_id', $accessibleAreas)
                            ->orWhereHas('technicianSchedules', function($sq) {
                                $sq->where('technician_id', auth()->id())->where('type', 'survey');
                            })
                            ->orWhereHas('surveys', function($sq) {
                                $sq->where('surveyor_id', auth()->id());
                            });
                    });
                } elseif (auth()->user()->can('customers_survey_view_assigned') || auth()->user()->hasRole('teknisi')) {
                    $openCustomerQuery->where(function($sub) {
                        $sub->whereHas('technicianSchedules', function($sq) {
                            $sq->where('technician_id', auth()->id())->where('type', 'survey');
                        })
                        ->orWhereHas('surveys', function($sq) {
                            $sq->where('surveyor_id', auth()->id());
                        });
                    });
                }
            }
            $openCustomer = $openCustomerQuery->find($request->open_report);
        }

        return Inertia::render('Customers/Survey', [
            'customers' => $customers,
            'availableOdps' => fn () => Odp::active()->with(['odc.olt', 'odc.area', 'area', 'ports'])->get(),
            'technicians' => fn () => User::where(function ($q) {
                    $q->where('is_active', true)->orWhereNull('is_active');
                })
                ->with('employee')
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'email', 'role']),
            'areas' => fn () => \App\Models\Area::orderBy('name')->pluck('name')->all(),
            'stats' => $stats,
            'filters' => $request->only(['search', 'tab', 'technician_id', 'date', 'area', 'open_report']),
            'openCustomer' => $openCustomer,
        ]);
    }

    /**
     * Halaman Instalasi (Jadwal, Lapor, Audit)
     */
    public function installed(Request $request): Response
    {
        $baseQuery = Customer::whereIn('status', ['installing', 'active']);
        
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_installed_view_all')) {
            if (auth()->user()->can('customers_installed_view_area')) {
                $accessibleAreas = auth()->user()->getAccessibleAreaIds();
                $baseQuery->where(function($sub) use ($accessibleAreas) {
                    $sub->whereIn('area_id', $accessibleAreas)
                        ->orWhereHas('technicianSchedules', function ($sq) {
                            $sq->where('technician_id', auth()->id())->where('type', 'installation');
                        })
                        ->orWhere('sales_id', auth()->id());
                });
            } elseif (auth()->user()->can('customers_installed_view_assigned') || auth()->user()->hasRole('teknisi')) {
                $baseQuery->whereHas('technicianSchedules', function ($sq) {
                    $sq->where('technician_id', auth()->id())->where('type', 'installation');
                });
            } else {
                $baseQuery->where(function($q) {
                    $q->where('sales_id', auth()->id())
                      ->orWhereHas('technicianSchedules', function ($sq) {
                          $sq->where('technician_id', auth()->id())->where('type', 'installation');
                      });
                });
            }
        }
        
        $filteredBaseQuery = clone $baseQuery;
        if ($request->area || $request->area_id) {
            $this->applyAreaFilter($filteredBaseQuery, $request->area_id ?: $request->area);
        }
        if ($request->technician_id) {
            $techId = $request->technician_id;
            $filteredBaseQuery->whereHas('technicianSchedules', function($sq) use ($techId) {
                $sq->where('technician_id', $techId)->where('type', 'installation');
            });
        }
        if ($request->date_from) {
            $dateFrom = $request->date_from;
            $filteredBaseQuery->where(function($sub) use ($dateFrom) {
                $sub->whereHas('technicianSchedules', function($ts) use ($dateFrom) {
                    $ts->where('scheduled_date', '>=', $dateFrom)->where('type', 'installation');
                })
                ->orWhereDate('registration_date', '>=', $dateFrom)
                ->orWhereDate('activation_date', '>=', $dateFrom)
                ->orWhereDate('created_at', '>=', $dateFrom);
            });
        }
        if ($request->date_to) {
            $dateTo = $request->date_to;
            $filteredBaseQuery->where(function($sub) use ($dateTo) {
                $sub->whereHas('technicianSchedules', function($ts) use ($dateTo) {
                    $ts->where('scheduled_date', '<=', $dateTo)->where('type', 'installation');
                })
                ->orWhereDate('registration_date', '<=', $dateTo)
                ->orWhereDate('activation_date', '<=', $dateTo)
                ->orWhereDate('created_at', '<=', $dateTo);
            });
        }
        if ($request->search) {
            $filteredBaseQuery->search($request->search);
        }

        $stats = [
            'semua' => (clone $filteredBaseQuery)->count(),
            'jadwal_pasang' => (clone $filteredBaseQuery)
                ->where('status', 'installing')
                ->whereDoesntHave('technicianSchedules', fn($q) => $q->where('type', 'installation'))->count(),
            'laporan_pasang' => (clone $filteredBaseQuery)
                ->where('status', 'installing')
                ->whereHas('technicianSchedules', function($q) {
                    $q->where('type', 'installation')->where('status', 'scheduled');
                })->count(),
            'audit' => (clone $filteredBaseQuery)
                ->where('status', 'installing')
                ->where('is_audited', false)
                ->whereDoesntHave('technicianSchedules', function($q) {
                    $q->where('type', 'installation')->where('status', 'scheduled');
                })
                ->whereHas('ont', function ($q) {
                    $q->whereNotNull('rx_power');
                })->count(),
            'selesai_instalasi' => (clone $filteredBaseQuery)
                ->where(function($q) {
                    $q->where('status', 'installing')->where('is_audited', true)
                      ->orWhere('status', 'active');
                })
                ->count(),
        ];

        $customerQuery = clone $filteredBaseQuery;
        
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
            ->with(['package', 'surveys.odp', 'ont.odp.odc.olt', 'sales', 'technicianSchedules' => function ($q) {
                $q->where('type', 'installation')->with('technician');
            }])
            ->orderByRaw('COALESCE(registration_date, created_at) DESC, id DESC')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Installed', [
            'customers' => $customers,
            'technicians' => fn () => User::where(function ($q) {
                    $q->where('is_active', true)->orWhereNull('is_active');
                })
                ->with('employee')
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'email', 'role']),
            'availableOnts' => fn () => \App\Models\Ont::whereIn('status', ['Sudah Set', 'Belum Set/Baru Input', 'inactive'])
                ->whereNull('customer_id')
                ->get(),
            'materials' => fn () => \App\Models\Material::with('stocks')
                ->where(function ($q) {
                    $q->where('is_active', true)->orWhereNull('is_active');
                })
                ->orderBy('name')
                ->get(),
            'materialTransactions' => fn () => \App\Models\MaterialTransaction::where('type', 'in')
                ->with('items.material.stocks')
                ->latest()
                ->limit(100)
                ->get(),
            'packages' => fn () => \App\Models\InternetPackage::active()->get(),
            'areas' => fn () => \App\Models\Area::orderBy('name')->get(['id', 'name']),
            'stats' => $stats,
            'filters' => array_merge(['tab' => $currentTab ?: ''], $request->only(['search', 'tab', 'date_from', 'date_to', 'area', 'area_id', 'technician_id'])),
            'taxSettings' => fn () => [
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
        $applyAreaScope = function ($query) {
            if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_activation_view_all')) {
                if (auth()->user()->can('customers_activation_view_area')) {
                    $accessibleAreas = auth()->user()->getAccessibleAreaIds();
                    $query->where(function($sub) use ($accessibleAreas) {
                        $sub->whereIn('area_id', $accessibleAreas)
                            ->orWhere('sales_id', auth()->id())
                            ->orWhereHas('technicianSchedules', function ($sq) {
                                $sq->where('technician_id', auth()->id());
                            });
                    });
                } else {
                    $query->where(function($q) {
                        $q->where('sales_id', auth()->id())
                          ->orWhereHas('technicianSchedules', function ($sq) {
                              $sq->where('technician_id', auth()->id())->where('type', 'installation');
                          });
                    });
                }
            }
        };

        // 1. Antrean Menunggu Aktivasi (status installing & sudah diaudit)
        $waitingQuery = Customer::query()->where('status', 'installing')->where('is_audited', true);
        $applyAreaScope($waitingQuery);

        // 2. Riwayat Aktivasi (pelanggan yang sudah diaktivasi dan bukan berhenti/dibatalkan)
        $historyQuery = Customer::query()->where(function($q) {
            $q->whereNotNull('activation_date')
              ->orWhere('status', 'active');
        })->whereNotIn('status', ['installing', 'terminated', 'canceled']);
        $applyAreaScope($historyQuery);

        $statsWaiting = clone $waitingQuery;
        $statsHistory = clone $historyQuery;
        if ($request->area || $request->area_id) {
            $this->applyAreaFilter($statsWaiting, $request->area_id ?: $request->area);
            $this->applyAreaFilter($statsHistory, $request->area_id ?: $request->area);
        }
        if ($request->search) {
            $statsWaiting->search($request->search);
            $statsHistory->search($request->search);
        }
        $stats = [
            'aktivasi' => $statsWaiting->count(),
            'riwayat' => $statsHistory->count(),
        ];

        $currentTab = $request->input('tab', 'menunggu');
        if (!in_array($currentTab, ['menunggu', 'riwayat'])) {
            $currentTab = 'menunggu';
        }

        $activeQuery = ($currentTab === 'riwayat') ? clone $historyQuery : clone $waitingQuery;

        $customers = $activeQuery
            ->with([
                'package',
                'sales:id,name,role,booking_fee',
                'surveys.odp',
                'surveys.surveyor:id,name,role',
                'ont.odp.odc.olt',
                'technicianSchedules.technician:id,name,role,installation_fee,booking_fee',
            ])
            ->when($request->area || $request->area_id, fn($q) => $this->applyAreaFilter($q, $request->area_id ?: $request->area))
            ->when($request->date_from, function ($q, $dateFrom) use ($currentTab) {
                if ($currentTab === 'riwayat') {
                    $q->where(function($sub) use ($dateFrom) {
                        $sub->whereDate('activation_date', '>=', $dateFrom)
                            ->orWhereDate('updated_at', '>=', $dateFrom)
                            ->orWhereDate('created_at', '>=', $dateFrom);
                    });
                } else {
                    $q->where(function($sub) use ($dateFrom) {
                        $sub->whereDate('registration_date', '>=', $dateFrom)
                            ->orWhereDate('created_at', '>=', $dateFrom);
                    });
                }
            })
            ->when($request->date_to, function ($q, $dateTo) use ($currentTab) {
                if ($currentTab === 'riwayat') {
                    $q->where(function($sub) use ($dateTo) {
                        $sub->whereDate('activation_date', '<=', $dateTo)
                            ->orWhereDate('updated_at', '<=', $dateTo)
                            ->orWhereDate('created_at', '<=', $dateTo);
                    });
                } else {
                    $q->where(function($sub) use ($dateTo) {
                        $sub->whereDate('registration_date', '<=', $dateTo)
                            ->orWhereDate('created_at', '<=', $dateTo);
                    });
                }
            })
            ->search($request->search)
            ->when($currentTab === 'riwayat', function ($q) {
                $q->orderByRaw('COALESCE(activation_date, updated_at) DESC, id DESC');
            }, function ($q) {
                $q->orderByRaw('COALESCE(registration_date, created_at) DESC, id DESC');
            })
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Activation', [
            'customers' => $customers,
            'stats' => $stats,
            'packages' => fn () => \App\Models\InternetPackage::orderBy('price')->get(['id', 'name', 'price']),
            'areas' => fn () => DataScopeService::getScopedAreaNames($request->user(), 'customers_all'),
            'filters' => array_merge(['tab' => $currentTab], $request->only(['search', 'tab', 'date', 'area', 'date_from', 'date_to'])),
            'taxSettings' => fn () => [
                'tax_ppn' => \App\Models\Setting::get('tax_ppn', '0'),
                'tax_bhp' => \App\Models\Setting::get('tax_bhp', '0'),
                'tax_uso' => \App\Models\Setting::get('tax_uso', '0'),
            ],
            'billingSettings' => fn () => [
                'billing_cycle' => \App\Models\Setting::get('billing_cycle', 'anniversary'),
                'billing_type' => \App\Models\Setting::get('billing_type', 'prabayar'),
                'invoice_issue_date' => \App\Models\Setting::get('invoice_issue_date', '1'),
                'isolate_days' => \App\Models\Setting::get('isolate_days', '3'),
                'isolate_day' => \App\Models\Setting::get('isolate_day'),
                'isolate_time' => \App\Models\Setting::get('isolate_time', '00:00'),
                'prorata_formula' => \App\Models\Setting::get('prorata_formula', 'exact_days'),
            ],
            'masterFees' => fn () => [
                'booking' => (float) (\App\Models\MasterFee::where('type', 'Fee Booking')->where('is_active', true)->value('nominal') ?? 35000),
                'survey' => (float) (\App\Models\MasterFee::where('type', 'Fee Survey')->where('is_active', true)->value('nominal') ?? 15000),
                'pasang' => (float) (\App\Models\MasterFee::where('type', 'Fee Pasang')->where('is_active', true)->value('nominal') ?? 35000),
            ],
            'masterFeePasang' => fn () => (float) (\App\Models\MasterFee::where('type', 'Fee Pasang')->where('is_active', true)->value('nominal') ?? 35000),
        ]);
    }

    /**
     * Halaman Pelanggan Aktif
     */
    public function active(Request $request): RedirectResponse
    {
        return redirect()->route('customers.index', array_merge($request->query(), ['status' => 'active']));
    }

    /**
     * Halaman Pelanggan Isolir
     */
    public function isolir(Request $request): Response
    {
        return $this->index($request);
    }

    /**
     * Halaman Pelanggan Stop Permanen (terminated)
     */
    public function terminated(Request $request): Response
    {
        $baseQuery = Customer::where('status', 'terminated');

        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('customers_all_view_all')) {
            if (auth()->user()->can('customers_all_view_area')) {
                $baseQuery->whereIn('area_id', auth()->user()->getAccessibleAreaIds());
            } else {
                $baseQuery->where('sales_id', auth()->id());
            }
        }

        $tab = $request->query('tab', 'all');
        $period = $request->query('period');
        $card = $request->query('card');
        $areaId = $request->query('area_id') ?: $request->query('area');
        $packageId = $request->query('package_id');
        $ontStatus = $request->query('ont_status');
        $billingStatus = $request->query('billing_status');
        $search = $request->query('search');

        // Stats kontekstual berdasarkan Area & Search
        $statsQuery = clone $baseQuery;
        if ($areaId) {
            $this->applyAreaFilter($statsQuery, $areaId);
        }
        if ($search) {
            $statsQuery->search($search);
        }

        $totalTerminated = (clone $statsQuery)->count();
        $thisMonthTerminated = (clone $statsQuery)->whereMonth('cancel_date', now()->month)
                                                 ->whereYear('cancel_date', now()->year)->count();
        
        $pendingOntCount = (clone $statsQuery)->whereHas('ont')->count();

        $totalPiutang = (float) Invoice::whereIn('customer_id', (clone $statsQuery)->select('id'))
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('is_reseller_balance', false)
            ->sum('amount');

        $under4MonthsCount = (clone $statsQuery)
            ->whereRaw('TIMESTAMPDIFF(MONTH, COALESCE(activation_date, registration_date, created_at), COALESCE(cancel_date, NOW())) < 4')
            ->count();

        $above4MonthsCount = (clone $statsQuery)
            ->whereRaw('TIMESTAMPDIFF(MONTH, COALESCE(activation_date, registration_date, created_at), COALESCE(cancel_date, NOW())) >= 4')
            ->count();

        $stats = [
            'total' => $totalTerminated,
            'this_month' => $thisMonthTerminated,
            'pending_ont' => $pendingOntCount,
            'total_piutang' => $totalPiutang,
            'under_4_months' => $under4MonthsCount,
            'above_4_months' => $above4MonthsCount,
        ];

        // Query
        $customersQuery = (clone $baseQuery)
            ->with([
                'package', 
                'areaModel', 
                'ont.odp', 
                'sales:id,name',
                'clawbackCommission',
                'clawbackSurveyCommission.affiliate:id,name',
                'invoices' => function($q) {
                    $q->whereIn('status', ['unpaid', 'partial'])
                      ->where('is_reseller_balance', false)
                      ->orderBy('due_date', 'asc');
                }
            ])
            ->withCount(['invoices as unpaid_invoices_count' => function ($q) {
                $q->whereIn('status', ['unpaid', 'partial'])->where('is_reseller_balance', false);
            }])
            ->withSum(['invoices as total_unpaid_amount' => function ($q) {
                $q->whereIn('status', ['unpaid', 'partial'])->where('is_reseller_balance', false);
            }], 'amount')
            ->search($search)
            ->when($areaId, fn($q) => $this->applyAreaFilter($q, $areaId))
            ->when($packageId, fn($q, $pkg) => $q->where('package_id', $pkg))
            ->when($period === 'this_month' || $card === 'this_month', function($q) {
                $q->whereMonth('cancel_date', now()->month)->whereYear('cancel_date', now()->year);
            })
            ->when($ontStatus === 'pending', fn($q) => $q->whereHas('ont'))
            ->when($ontStatus === 'returned', fn($q) => $q->whereDoesntHave('ont'))
            ->when($billingStatus === 'unpaid', function($q) {
                $q->whereHas('invoices', function($iq) {
                    $iq->whereIn('status', ['unpaid', 'partial'])->where('is_reseller_balance', false);
                });
            })
            ->when($billingStatus === 'paid', function($q) {
                $q->whereDoesntHave('invoices', function($iq) {
                    $iq->whereIn('status', ['unpaid', 'partial'])->where('is_reseller_balance', false);
                });
            })
            ->when($tab === 'under_4_months', function ($q) {
                $q->whereRaw('TIMESTAMPDIFF(MONTH, COALESCE(activation_date, registration_date, created_at), COALESCE(cancel_date, NOW())) < 4');
            })
            ->when($tab === 'above_4_months', function ($q) {
                $q->whereRaw('TIMESTAMPDIFF(MONTH, COALESCE(activation_date, registration_date, created_at), COALESCE(cancel_date, NOW())) >= 4');
            })
            ->orderByDesc('cancel_date')
            ->orderByDesc('updated_at');

        $customers = $customersQuery->paginate($request->per_page ?? 15)->withQueryString();

        $customers->getCollection()->each(function ($customer) {
            $customer->append('active_duration');
        });

        return Inertia::render('Customers/Terminated', [
            'customers' => $customers,
            'packages' => fn () => InternetPackage::active()->get(['id', 'name', 'price']),
            'areas' => fn () => DataScopeService::getScopedAreas($request->user(), 'customers_all'),
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'area_id' => $areaId,
                'package_id' => $packageId,
                'ont_status' => $ontStatus,
                'billing_status' => $billingStatus,
                'period' => $period,
                'tab' => $tab,
            ],
            'company' => [
                'name' => Setting::get('company_name', config('app.name', 'ISP Provider')),
                'phone' => Setting::get('company_phone', ''),
            ]
        ]);
    }

    /**
     * Stop Permanen Pelanggan (dari menu manapun)
     */
    public function terminate(Request $request, Customer $customer, RadiusService $radius)
    {
        $request->validate([
            'cancel_reason' => 'required|string',
            'cancel_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $cancelDate = $request->cancel_date ?: now()->toDateString();

        // ── Otomatis Pinalti Stop Dini (< 4 Bulan) ──
        $clawback = \App\Services\AffiliateService::handleEarlyTerminationPenalty($customer, $cancelDate);

        $notes = $customer->notes;
        if ($request->notes) {
            $notes = $notes ? $notes . ' | ' . $request->notes : $request->notes;
        }

        $penaltyMessages = [];
        if ($clawback && $clawback['penalty_applied']) {
            $penaltyNotes = [];

            if (!empty($clawback['booking_penalty'])) {
                $bp = $clawback['booking_penalty'];
                $formattedAmount = 'Rp ' . number_format($bp['amount'], 0, ',', '.');
                $targetStr = $bp['is_employee'] 
                    ? "Slip Gaji / Payroll Karyawan {$bp['name']}" 
                    : "saldo dompet afiliasi {$bp['name']}";
                $penaltyNotes[] = "[Pinalti Stop Dini < 4 Bln]: Fee booking {$formattedAmount} otomatis dipotong pada {$targetStr} (Masa aktif: {$clawback['duration']})";
                $penaltyMessages[] = "Fee booking {$formattedAmount} otomatis dipotong dari {$targetStr}";
            }

            if (!empty($clawback['survey_penalty'])) {
                $sp = $clawback['survey_penalty'];
                $formattedAmount = 'Rp ' . number_format($sp['amount'], 0, ',', '.');
                $targetStr = $sp['is_employee'] 
                    ? "Slip Gaji / Payroll Teknisi {$sp['name']}" 
                    : "saldo dompet {$sp['name']}";
                $penaltyNotes[] = "[Pinalti Stop Dini < 4 Bln]: Fee survey {$formattedAmount} otomatis dipotong pada {$targetStr} (Masa aktif: {$clawback['duration']})";
                $penaltyMessages[] = "Fee survey {$formattedAmount} otomatis dipotong dari {$targetStr}";
            }

            if (!empty($penaltyNotes)) {
                $combinedNotes = implode(' | ', $penaltyNotes);
                $notes = $notes ? $notes . " | {$combinedNotes}" : $combinedNotes;
            }
        }

        $customer->update([
            'status' => 'terminated',
            'cancel_date' => $cancelDate,
            'cancel_reason' => $request->cancel_reason,
            'notes' => $notes,
        ]);

        // Putus koneksi dan hapus user RADIUS
        $radius->guard(function($r) use ($customer) {
            $r->syncCustomer($customer);
            $accounts = $r->customerAccounts($customer);
            foreach (array_keys($accounts) as $username) {
                $r->removeUser($username);
                $r->disconnect($username);
            }
        });

        $msg = "Pelanggan {$customer->name} ({$customer->customer_code}) berhasil di-Stop Permanen dan dipindahkan ke menu Stop Permanen.";
        if (!empty($penaltyMessages)) {
            $msg .= " ⚠️ Pinalti Stop Dini (< 4 Bulan) Diterapkan (Masa aktif: {$clawback['duration']}): " . implode(', dan ', $penaltyMessages) . ".";
        }

        return back()->with('success', $msg);
    }

    /**
     * Reaktivasi Pelanggan Stop Permanen
     */
    public function reactivate(Request $request, Customer $customer, RadiusService $radius)
    {
        $request->validate([
            'package_id' => 'nullable|exists:internet_packages,id',
            'activation_date' => 'nullable|date',
            'target_status' => 'nullable|in:active,installing,survey',
        ]);

        $targetStatus = $request->target_status ?: 'active';
        $packageId = $request->package_id ?: $customer->package_id;
        $actDate = $request->activation_date ?: now()->toDateString();

        $customer->update([
            'status' => $targetStatus,
            'package_id' => $packageId,
            'activation_date' => $targetStatus === 'active' ? $actDate : $customer->activation_date,
            'cancel_date' => null,
            'cancel_reason' => null,
            'suspend_start' => null,
            'suspend_end' => null,
        ]);

        if ($targetStatus === 'active') {
            $radius->guard(function($r) use ($customer) {
                $r->syncCustomer($customer);
            });
        }

        return back()->with('success', "Pelanggan {$customer->name} ({$customer->customer_code}) berhasil direaktivasi (Status: {$customer->status_label}).");
    }

    /**
     * Tarik ONT dari Pelanggan ke Stok Gudang
     */
    public function pullOnt(Request $request, Customer $customer)
    {
        $ont = $customer->ont;
        if (!$ont) {
            return back()->withErrors(['error' => 'Pelanggan ini tidak memiliki perangkat ONT yang terpasang.']);
        }

        $sn = $ont->serial_number;

        $ont->update([
            'customer_id' => null,
            'status' => 'in_stock',
            'description' => 'Ditarik dari pelanggan ' . $customer->name . ' (' . $customer->customer_code . ') pada ' . now()->format('d/m/Y'),
        ]);

        return back()->with('success', "Perangkat ONT {$sn} berhasil dilepas dan dikembalikan ke stok gudang.");
    }

    /**
     * Pemutihan / Penghapusan Sisa Tagihan Pelanggan Stop Permanen
     */
    public function writeOffInvoices(Request $request, Customer $customer)
    {
        $invoices = $customer->invoices()->whereIn('status', ['unpaid', 'partial'])->get();
        if ($invoices->isEmpty()) {
            return back()->with('info', 'Tidak ada tagihan tertunggak untuk pelanggan ini.');
        }

        foreach ($invoices as $inv) {
            $inv->update([
                'status' => 'cancelled',
                'notes' => ($inv->notes ? $inv->notes . ' | ' : '') . 'Pemutihan tagihan stop permanen pada ' . now()->format('d/m/Y'),
            ]);
        }

        return back()->with('success', "Sebanyak {$invoices->count()} tagihan pelanggan {$customer->name} berhasil dihapuskan (pemutihan).");
    }

    /**
     * Cetak Berita Acara Berhenti Berlangganan (BAB)
     */
    public function printTermination(Customer $customer)
    {
        $customer->load(['package', 'areaModel', 'ont', 'sales']);

        $company = [
            'name' => Setting::get('company_name', config('app.name', 'ISP Management')),
            'address' => Setting::get('company_address', 'Layanan Internet Cepat & Stabil'),
            'phone' => Setting::get('company_phone', ''),
            'email' => Setting::get('company_email', ''),
            'logo' => Setting::get('company_logo', null),
        ];

        return view('print.termination', compact('customer', 'company'));
    }

    /**
     * Cetak Formulir & Bukti Pendaftaran Booking Pelanggan
     */
    public function printBooking(Customer $customer)
    {
        $customer->load(['package', 'areaModel', 'sales.employee']);

        $company = [
            'name' => Setting::get('company_name', config('app.name', 'ISP Management')),
            'address' => Setting::get('company_address', 'Layanan Internet Cepat & Stabil'),
            'phone' => Setting::get('company_phone', ''),
            'email' => Setting::get('company_email', ''),
            'logo' => Setting::get('company_logo', null),
        ];

        return view('print.booking', compact('customer', 'company'));
    }

    /**
     * Cetak Invoice Pasang Baru (PSB) & Bukti Aktivasi
     */
    public function printPsb(Customer $customer)
    {
        $customer->load(['package', 'areaModel', 'sales.employee', 'ont.odp.odc.olt']);

        $company = [
            'name' => Setting::get('company_name', config('app.name', 'ISP Management')),
            'address' => Setting::get('company_address', 'Layanan Internet Cepat & Stabil'),
            'phone' => Setting::get('company_phone', ''),
            'email' => Setting::get('company_email', ''),
            'website' => Setting::get('company_website', ''),
            'logo' => Setting::get('company_logo', null),
        ];

        return view('print.invoice-psb', compact('customer', 'company'));
    }

    /**
     * Form tambah pelanggan baru (booking)
     */
    public function create(): Response
    {
        $isAffiliatePortal = request()->routeIs('affiliate.booking.*');
        if ($isAffiliatePortal) {
            abort_unless(\App\Services\AffiliateService::ensureAffiliateProfile(auth()->user())->status === 'active', 403, 'Profil afiliasi Anda belum aktif.');
        }
        // Seluruh karyawan bebas booking di semua area/wilayah
        $areas = \App\Models\Area::orderBy('name')->pluck('name')->all();
        
        $sales = User::where('is_active', true)
            ->whereNotIn('role', ['customer'])
            ->when($isAffiliatePortal, fn ($query) => $query->whereKey(auth()->id()))
            ->with(['employee:id,email,phone,name,position'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'role'])
            ->map(function ($u) {
                $pos = $u->job_position;
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'role' => $pos,
                    'job_position' => $pos,
                    'email' => $u->email,
                    'display_name' => "{$u->name} ({$pos})",
                ];
            });

        // Kumpulkan desa yang pernah di-input saat booking per area untuk saran autocomplete
        $areaVillagesMap = [];
        foreach (\App\Models\Area::whereIn('name', $areas)->when($isAffiliatePortal, fn ($query) => $query->whereRaw('1 = 0'))->get() as $a) {
            $vList = collect();
            $custAddrs = Customer::where(function($q) use ($a) {
                $q->where('area_id', $a->id)->orWhere('area', $a->name);
            })->whereNotNull('address')->pluck('address');

            foreach ($custAddrs as $addr) {
                if (preg_match_all('/(?:Kel\.?|Kelurahan|Desa)\s*([a-zA-Z0-9\s]+?)(?=\s+(?:Kec|Kab|RT|RW|No|\d)|[,\n\r\.\-]|$)/i', $addr, $matches)) {
                    foreach ($matches[1] as $m) {
                        $val = trim($m);
                        if (strlen($val) >= 2 && strlen($val) <= 40 && !in_array(strtolower($val), ['kec', 'kab', 'rt', 'rw', 'jalan', 'jl'])) {
                            $vList->push(ucwords(strtolower($val)));
                        }
                    }
                }
            }
            $areaVillagesMap[$a->name] = $vList->unique()->sort()->values()->all();
        }

        $user = auth()->user();
        $userRole = strtolower(trim($user?->role ?? ''));
        $isInternalStaff = in_array($userRole, [
            'admin', 'super admin', 'superadmin', 'super-admin', 
            'cs', 'admin cs', 'manager', 'noc', 'kepala teknisi', 'teknisi', 'maintenance', 'dispatcher', 'finance', 'hrd'
        ]) || ($user && method_exists($user, 'hasRole') && $user->hasRole('admin'));

        $isAffiliate = $isAffiliatePortal || (!$isInternalStaff && ($userRole === 'affiliate' || $userRole === 'reseller'));

        $packagesQuery = InternetPackage::active();
        if ($isAffiliatePortal) {
            $packagesQuery->visibleToSales();
        }

        return Inertia::render('Customers/Create', [
            'packages' => $packagesQuery->get(),
            'sales' => $sales,
            'areas' => $areas,
            'area_villages' => $areaVillagesMap,
            'isAffiliate' => $isAffiliate,
            'affiliatePortal' => $isAffiliatePortal,
            'taxSettings' => [
                'tax_ppn' => (float) \App\Models\Setting::get('tax_ppn', '0'),
                'tax_bhp' => (float) \App\Models\Setting::get('tax_bhp', '0'),
                'tax_uso' => (float) \App\Models\Setting::get('tax_uso', '0'),
            ],
        ]);
    }

    /**
     * Simpan pelanggan baru
     */
    public function store(Request $request): RedirectResponse
    {
        $isAffiliatePortal = $request->routeIs('affiliate.booking.store');
        $availablePackageRule = Rule::exists('internet_packages', 'id')->where(function ($query) use ($isAffiliatePortal) {
            $query->where('is_active', true);

            if ($isAffiliatePortal) {
                $query->where('is_visible_to_sales', true);
            }
        });

        if (!$isAffiliatePortal && !auth()->user()->can('customers_booking_create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuat booking.');
        }

        if ($isAffiliatePortal) {
            $profile = \App\Services\AffiliateService::ensureAffiliateProfile($request->user());
            abort_unless($profile->status === 'active', 403, 'Profil afiliasi Anda belum aktif.');
            $request->merge([
                'sales_id' => $request->user()->id,
                'referral_code' => $profile->referral_code,
                'service_status' => 'berbayar',
                'is_reseller' => false,
            ]);
            $request->validate([
                'area' => ['required', 'string', 'exists:areas,name'],
                'package_id' => ['required', $availablePackageRule],
            ], [
                'package_id.exists' => 'Paket ini tidak tersedia untuk Portal Sales.',
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:customers,email',
                function ($attribute, $value, $fail) {
                    if (\App\Models\User::where('email', $value)->exists()) {
                        $fail('Email ini sudah digunakan oleh akun login sistem lain.');
                    }
                }
            ],
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'area' => 'nullable|string',
            'identity_photo' => 'nullable|image|max:10240', // Max 10MB
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'package_id' => [
                'nullable',
                $availablePackageRule,
            ],
            'notes' => 'nullable|string',
            'base_amount' => 'nullable|numeric|min:0',
            'registration_date' => 'required|date',
            'installation_fee' => 'nullable|numeric|min:0',
            'sales_id' => 'nullable|exists:users,id',
            'referral_code' => 'nullable|string|max:50',
            'billing_type' => 'nullable|in:prabayar,pascabayar',
            'billing_cycle' => 'nullable|in:default,anniversary,calendar,custom_date',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'is_reseller' => 'boolean',
            'service_status' => 'nullable|in:berbayar,gratis,free_home',
            'enable_tax' => 'nullable|boolean',
            'tax_ppn' => 'nullable|numeric|min:0|max:100',
            'tax_bhp' => 'nullable|numeric|min:0|max:100',
            'tax_uso' => 'nullable|numeric|min:0|max:100',
        ], [
            'email.unique' => 'Email ini sudah terdaftar pada pelanggan lain. Silakan gunakan email berbeda.',
            'email.email' => 'Format penulisan email tidak valid.',
        ]);

        $user = auth()->user();
        $userRole = strtolower(trim($user?->role ?? ''));
        $isSuperAdmin = in_array($userRole, ['admin', 'super admin', 'superadmin', 'super-admin']) 
            || ($user && method_exists($user, 'hasRole') && ($user->hasRole('super admin') || $user->hasRole('admin')))
            || ($user && $user->id === 1);

        if (!$isSuperAdmin) {
            // Selain Super Admin: Terkunci otomatis atas nama akun staf yang sedang login
            $validated['sales_id'] = $user?->id;
        } else {
            // Untuk Super Admin: Jika memilih tanpa sales/referral, biarkan NULL murni
            $validated['sales_id'] = !empty($validated['sales_id']) ? $validated['sales_id'] : null;
        }

        if ($request->hasFile('identity_photo')) {
            $validated['identity_photo'] = $request->file('identity_photo')->store('ktp', 'public');
        }

        // Auto-create new area if not exists in master data
        if (!empty($validated['area'])) {
            $areaModel = \App\Models\Area::firstOrCreate(['name' => $validated['area']]);
            $validated['area_id'] = $areaModel->id;
        }

        $user = auth()->user();
        $userRole = strtolower(trim($user?->role ?? ''));
        $isInternalStaff = in_array($userRole, [
            'admin', 'super admin', 'superadmin', 'super-admin', 
            'cs', 'admin cs', 'manager', 'noc', 'kepala teknisi', 'teknisi', 'maintenance', 'dispatcher', 'finance', 'hrd'
        ]) || ($user && method_exists($user, 'hasRole') && $user->hasRole('admin'));

        $isAffiliate = !$isInternalStaff && ($userRole === 'affiliate' || $userRole === 'reseller');

        // Khusus booking dari akun affiliate, dilarang keras status gratis / free home (wajib berbayar)
        if ($isAffiliate) {
            $validated['service_status'] = 'berbayar';
        }

        $serviceStatus = $validated['service_status'] ?? 'berbayar';
        if ($serviceStatus === 'gratis') {
            $validated['base_amount'] = 0;
            $validated['installation_fee'] = 0;
        } elseif ($serviceStatus === 'free_home') {
            $validated['base_amount'] = 0;
            $validated['installation_fee'] = 0;
            $validated['package_id'] = null;
        } else {
            $packageQuery = InternetPackage::active();
            if ($isAffiliatePortal) {
                $packageQuery->visibleToSales();
            }
            $package = !empty($validated['package_id'])
                ? $packageQuery->find($validated['package_id'])
                : null;

            // Reseller voucher murni tidak memakai paket internet. Semua booking
            // internet wajib memilih paket aktif dari Master Data.
            if (!$package && empty($validated['is_reseller'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'package_id' => $isAffiliatePortal
                        ? 'Pilih paket yang tersedia untuk Portal Sales.'
                        : 'Pilih paket langganan aktif dari Master Data.',
                ]);
            }

            if ($package) {
                // Tarif bulanan mengikuti paket aktif yang berlaku di Master Data
                $validated['base_amount'] = $package->current_price;

                // Biaya Pasang Baru: Ditentukan secara otomatis dan cerdas oleh Master Paket Internet
                $selectedArea = $validated['area'] ?? null;
                $defaultAreaFee = $package->getInstallationFeeForArea($selectedArea);
                $allowedFees = $package->getAllowedInstallationFeesForArea($selectedArea);

                if ($isAffiliatePortal) {
                    $chosenFee = isset($request->installation_fee) && is_numeric($request->installation_fee)
                        ? (float)$request->installation_fee
                        : null;

                    if ($chosenFee !== null && in_array($chosenFee, $allowedFees, false)) {
                        $validated['installation_fee'] = $chosenFee;
                    } else {
                        $validated['installation_fee'] = $defaultAreaFee;
                    }
                } else {
                    // Internal Admin / Operator Workspace: Biaya pasang baru ditentukan secara mutlak oleh Paket Internet
                    $serviceStatus = $validated['service_status'] ?? 'berbayar';
                    if ($serviceStatus === 'gratis' || $serviceStatus === 'free_home') {
                        $validated['installation_fee'] = 0;
                    } else {
                        $validated['installation_fee'] = $defaultAreaFee;
                    }
                }
            } else {
                $validated['base_amount'] = 0;
                $validated['installation_fee'] = 0;
            }
        }

        $validated['status'] = 'booking'; // Ensure it goes to booking

        if (empty($validated['billing_type'])) {
            $validated['billing_type'] = \App\Models\Setting::get('billing_type', 'prabayar');
        }
        if (empty($validated['billing_cycle'])) {
            $validated['billing_cycle'] = 'default';
        }

        if ($request->has('enable_tax') && !$request->boolean('enable_tax')) {
            $validated['tax_ppn'] = 0;
            $validated['tax_bhp'] = 0;
            $validated['tax_uso'] = 0;
        } else {
            $validated['tax_ppn'] = (float) ($request->input('tax_ppn') ?? \App\Models\Setting::get('tax_ppn', '0'));
            $validated['tax_bhp'] = (float) ($request->input('tax_bhp') ?? \App\Models\Setting::get('tax_bhp', '0'));
            $validated['tax_uso'] = (float) ($request->input('tax_uso') ?? \App\Models\Setting::get('tax_uso', '0'));
        }

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

        return redirect()->route($isAffiliatePortal ? 'affiliate.dashboard' : 'customers.booking')
            ->with('success', 'Data booking pelanggan berhasil ditambahkan.');
    }

    /**
     * Detail pelanggan (profil lengkap + ONT + ODP)
     */
    public function show(Customer $customer): Response
    {
        $customer->load([
            'package',
            'areaModel',
            'ont.odp.odc.olt',
            'ont.odp.odc.pon',
            'invoices' => fn ($q) => $q->orderByDesc('period_year')->orderByDesc('period_month')->limit(12),
            'invoices.payments',
            'tickets' => fn ($q) => $q->orderByDesc('created_at')->limit(10),
            'tickets.assignee',
            'surveys.odp.odc',
            'surveys.surveyor',
            'technicianSchedules.technician',
            'user',
            'sales:id,name',
        ]);

        $materialRequests = \App\Models\MaterialTransaction::with('items.material')
            ->where(function($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                  ->orWhere(function($sub) use ($customer) {
                      $sub->where('source_type', 'instalasi')->where('source_id', $customer->id);
                  });
            })
            ->latest()
            ->get();

        $installationReturns = \App\Models\MaterialTransaction::with(['items.material', 'user:id,name'])
            ->where(function($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                  ->orWhere(function($sub) use ($customer) {
                      $sub->where('source_type', 'instalasi')->where('source_id', $customer->id);
                  });
            })
            ->where(function($q) {
                $q->where('request_type', 'return_handover')
                  ->orWhere('transaction_number', 'like', 'RTR-%');
            })
            ->latest()
            ->get();

        $installationUsages = \App\Models\MaterialTransaction::with('items.material')
            ->where(function($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                  ->orWhere(function($sub) use ($customer) {
                      $sub->where('source_type', 'instalasi')->where('source_id', $customer->id);
                  });
            })
            ->where('transaction_number', 'like', 'OUT-INSTALASI-%')
            ->latest()
            ->get();

        $materialTransactions = \App\Models\MaterialTransaction::where('type', 'in')
            ->with('items.material.stocks')
            ->latest()
            ->limit(100)
            ->get();

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
            'source' => request()->query('source'),
            'availableOdps' => Odp::active()->with(['odc.olt', 'onts.customer', 'ports'])->get(),
            'availableOnts' => Ont::where('status', 'Sudah Set')->whereNull('customer_id')->get(),
            'technicians' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone', 'role']),
            'packages' => InternetPackage::active()->get(),
            'areas' => \App\Models\Area::orderBy('name')->get(['id', 'name']),
            'salesUsers' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'materials' => \App\Models\Material::orderBy('name')->get(),
            'materialRequests' => $materialRequests,
            'installationReturns' => $installationReturns,
            'installationUsages' => $installationUsages,
            'materialTransactions' => $materialTransactions,
        ]);
    }

    /**
     * Buat akun login untuk pelanggan
     */
    public function createAccount(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $email = trim(strtolower($validated['email']));
        $password = $validated['password'];

        // Cek apakah email sudah terdaftar di tabel users
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            // Cek apakah user ini sudah terikat dengan pelanggan LAIN
            $otherCustomer = Customer::where('user_id', $existingUser->id)
                ->where('id', '!=', $customer->id)
                ->first();

            if ($otherCustomer) {
                return back()->withErrors([
                    'email' => "Email ini sudah digunakan oleh akun pelanggan lain ({$otherCustomer->name} - {$otherCustomer->customer_code})."
                ]);
            }

            // Tautkan akun user yang sudah ada ke pelanggan ini
            DB::transaction(function () use ($customer, $existingUser, $email, $password) {
                $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer']);
                if (method_exists($existingUser, 'hasRole') && !$existingUser->hasRole('customer')) {
                    $existingUser->assignRole($role);
                }

                $updateData = ['is_active' => true];
                if (!empty($password)) {
                    $updateData['password'] = bcrypt($password);
                }
                $existingUser->update($updateData);

                $customer->update([
                    'user_id' => $existingUser->id,
                    'email' => $email
                ]);
            });

            return back()->with('success', 'Akun pengguna yang sudah ada berhasil ditautkan ke pelanggan ini.');
        }

        // Jika user belum ada, buat user baru
        DB::transaction(function () use ($customer, $email, $password) {
            $user = User::create([
                'name' => $customer->name,
                'email' => $email,
                'password' => bcrypt($password),
                'role' => 'customer',
                'is_active' => true,
            ]);

            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer']);
            $user->assignRole($role);

            $customer->update([
                'user_id' => $user->id,
                'email' => $email
            ]);
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
            'password' => 'nullable|string|min:6',
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
        
        $areas = \App\Models\Area::orderBy('name')->pluck('name')->all();

        $sales = User::where('is_active', true)
            ->whereNotIn('role', ['customer'])
            ->with(['employee:id,email,phone,name,position'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'role'])
            ->map(function ($u) {
                $pos = $u->job_position;
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'role' => $pos,
                    'job_position' => $pos,
                    'email' => $u->email,
                    'display_name' => "{$u->name} ({$pos})",
                ];
            });

        $user = auth()->user();
        $userRole = strtolower(trim($user?->role ?? ''));
        $isInternalStaff = in_array($userRole, [
            'admin', 'super admin', 'superadmin', 'super-admin', 
            'cs', 'admin cs', 'manager', 'noc', 'kepala teknisi', 'teknisi', 'maintenance', 'dispatcher', 'finance', 'hrd'
        ]) || ($user && method_exists($user, 'hasRole') && $user->hasRole('admin'));

        $isAffiliate = !$isInternalStaff && ($userRole === 'affiliate' || $userRole === 'reseller');

        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
            'packages' => InternetPackage::active()->get(),
            'availableOdps' => Odp::active()->with('odc.olt')->get(),
            'sales' => $sales,
            'areas' => $areas,
            'isAffiliate' => $isAffiliate,
            'taxSettings' => [
                'tax_ppn' => (float) \App\Models\Setting::get('tax_ppn', '0'),
                'tax_bhp' => (float) \App\Models\Setting::get('tax_bhp', '0'),
                'tax_uso' => (float) \App\Models\Setting::get('tax_uso', '0'),
            ],
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
                Rule::unique('customers', 'email')->ignore($customer->id),
                function ($attribute, $value, $fail) use ($customer) {
                    $userExists = \App\Models\User::where('email', $value)
                        ->when($customer->user_id, fn($q) => $q->where('id', '!=', $customer->user_id))
                        ->exists();
                    if ($userExists) {
                        $fail('Email ini sudah digunakan oleh akun login sistem lain.');
                    }
                }
            ],
            'address' => 'required|string',
            'area' => 'nullable|string',
            'identity_photo' => 'nullable|image|max:10240',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'package_id' => 'nullable|exists:internet_packages,id',
            'status' => ['required', Rule::in(['booking', 'survey', 'installing', 'active', 'suspended', 'terminated'])],
            'notes' => 'nullable|string',
            'base_amount' => 'nullable|numeric|min:0',
            'registration_date' => 'nullable|date',
            'installation_fee' => 'nullable|numeric|min:0',
            'sales_id' => 'nullable|exists:users,id',
            'referral_code' => 'nullable|string|max:50',
            'billing_type' => 'nullable|in:prabayar,pascabayar',
            'billing_cycle' => 'nullable|in:default,anniversary,calendar,custom_date',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'is_reseller' => 'boolean',
            'service_status' => 'nullable|in:berbayar,gratis,free_home',
            'enable_tax' => 'nullable|boolean',
            'tax_ppn' => 'nullable|numeric|min:0|max:100',
            'tax_bhp' => 'nullable|numeric|min:0|max:100',
            'tax_uso' => 'nullable|numeric|min:0|max:100',
        ], [
            'email.unique' => 'Email ini sudah terdaftar pada pelanggan lain. Silakan gunakan email berbeda.',
            'email.email' => 'Format penulisan email tidak valid.',
        ]);

        if ($request->has('enable_tax') && !$request->boolean('enable_tax')) {
            $validated['tax_ppn'] = 0;
            $validated['tax_bhp'] = 0;
            $validated['tax_uso'] = 0;
        }

        $user = auth()->user();
        $userRole = strtolower(trim($user?->role ?? ''));
        $isSuperAdmin = in_array($userRole, ['admin', 'super admin', 'superadmin', 'super-admin']) 
            || ($user && method_exists($user, 'hasRole') && ($user->hasRole('super admin') || $user->hasRole('admin')))
            || ($user && $user->id === 1);

        if (!$isSuperAdmin) {
            unset($validated['sales_id']);
        } else {
            $validated['sales_id'] = !empty($validated['sales_id']) ? $validated['sales_id'] : null;
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

        $user = auth()->user();
        $userRole = strtolower(trim($user?->role ?? ''));
        $isInternalStaff = in_array($userRole, [
            'admin', 'super admin', 'superadmin', 'super-admin', 
            'cs', 'admin cs', 'manager', 'noc', 'kepala teknisi', 'teknisi', 'maintenance', 'dispatcher', 'finance', 'hrd'
        ]) || ($user && method_exists($user, 'hasRole') && $user->hasRole('admin'));

        $isAffiliate = !$isInternalStaff && ($userRole === 'affiliate' || $userRole === 'reseller');

        if ($isAffiliate && isset($validated['service_status'])) {
            $validated['service_status'] = 'berbayar';
        }

        $targetServiceStatus = $validated['service_status'] ?? $customer->service_status;
        if (in_array($targetServiceStatus, ['gratis', 'free_home'])) {
            $validated['base_amount'] = 0;
            if ($targetServiceStatus === 'free_home') {
                $validated['package_id'] = null;
            }
            \App\Models\Invoice::where('customer_id', $customer->id)
                ->whereIn('status', ['unpaid', 'partial'])
                ->delete();
        } else {
            // Berbayar: jika sebelumnya free_home dan sekarang berpindah ke berbayar dengan paket
            if ($customer->service_status === 'free_home' && !empty($validated['package_id'])) {
                $pkg = InternetPackage::find($validated['package_id']);
                if (empty($validated['base_amount']) || $validated['base_amount'] == 0) {
                    $validated['base_amount'] = $pkg?->price ?? 0;
                }
            }
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
     * Hapus permanen satu data pelanggan beserta seluruh relasi & data terhubung
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $user = auth()->user();
        $canDelete = $user && (
            $user->hasRole('admin') ||
            $user->can('customers_delete') ||
            $user->can('customers_booking_delete') ||
            $user->can('customers_survey_delete') ||
            $user->can('customers_installed_delete') ||
            $user->can('menu_customers_booking') ||
            $user->can('menu_customers_survey') ||
            $user->can('menu_customers_installed') ||
            $user->can('menu_customers_all')
        );

        if (!$canDelete) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus data pelanggan.');
        }

        $customerName = $customer->name;
        $customerCode = $customer->customer_code;

        DB::transaction(function () use ($customer, $customerName, $customerCode) {
            \App\Models\AuditLog::createLog(
                'Hapus Pelanggan',
                $customer,
                $customer->status,
                'deleted',
                "Pelanggan {$customerName} ({$customerCode}) beserta seluruh perangkat dan data terhubung berhasil dihapus secara bersih."
            );

            // Customer::deleting event otomatis memicu purgeConnectedData()
            $customer->delete();
        });

        if (request()->routeIs('customers.show') || str_contains(url()->previous(), "/customers/{$customer->id}")) {
            return redirect()->route('customers.index')->with('success', "Pelanggan {$customerName} ({$customerCode}) dan seluruh data terhubung berhasil dihapus.");
        }

        return redirect()->back()->with('success', "Pelanggan {$customerName} ({$customerCode}) dan seluruh data terhubung berhasil dihapus.");
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
        $pendingRequest = \App\Models\MaterialTransaction::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->where(function($q) {
                $q->where('purpose', 'like', '%Request%')
                  ->orWhere('transaction_number', 'like', 'REQ-%');
            })
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
        if ($request->has('rx_power') && filled($request->rx_power)) {
            $parsedRx = \App\Services\DataMigrationService::parseRxPower($request->rx_power);
            $request->merge(['rx_power' => number_format($parsedRx, 2, '.', '')]);
        }

        $rules = [
            'odp_id' => 'required|exists:odps,id',
            'port_number' => 'required|integer|min:1',
            'rx_power' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
        ];

        // Photos are optional (can be uploaded now or edited manually later)
        $rules['photo_odp'] = 'nullable|image|max:10240';
        $rules['photo_installation'] = 'nullable|image|max:10240';
        $rules['photo_ont'] = 'nullable|image|max:10240';
        $rules['photo_customer'] = 'nullable|image|max:10240';
        $rules['photo_redaman'] = 'nullable|image|max:10240';

        $validated = $request->validate($rules);
        $validated['rx_power'] = number_format(\App\Services\DataMigrationService::parseRxPower($validated['rx_power'] ?? null), 2, '.', '');

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

        $usageDetails = [];
        try {
            DB::transaction(function () use ($validated, $customer, $odp, $request, $ont, &$usageDetails) {
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
                    'initial_rx_power' => $ont->initial_rx_power ?? ($validated['rx_power'] ?? null),
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
                    'initial_rx_power' => $validated['rx_power'] ?? null,
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
            $usageDetails = [];
            
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

            $allocatedDetails = [];
            $returnFormattedDetails = [];
            $returnTrx = null;

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
                        $unitStr = 'pcs';
                        if (str_contains($nameLower, 'kabel') || str_contains($nameLower, 'meter')) {
                            $unitStr = 'meter';
                        } elseif (str_contains($nameLower, 'cm')) {
                            $unitStr = 'cm';
                        } elseif (preg_match('/\((\d+(?:\.\d+)?)\s*([a-zA-Z]+)\)/', $item['name'], $uMatches)) {
                            $unitStr = $uMatches[2];
                        }
                        
                        if ($assignedQty > 0) {
                            $allocatedDetails[] = $material->name . ' (' . $assignedQty . ' ' . $unitStr . ')';
                        }

                        // Jika ada bekal awal yang dibawa teknisi (> usedQty), kurangi stok sebesar bekal yang dibawa,
                        // sisa yang tidak terpakai akan dikembalikan ke stok gudang area saat petugas menerima form serah terima.
                        $deductQty = ($assignedQty > 0) ? $assignedQty : $usedQty;

                        // 1. Catat pengeluaran material bekal / pemakaian instalasi
                        if ($deductQty > 0) {
                            $usedItems[] = [
                                'material' => $material,
                                'qty' => $deductQty,
                                'unit' => $unitStr,
                                'actual_used' => $usedQty,
                                'assigned_qty' => $assignedQty,
                            ];
                            $rincianNotesUsed[] = "- {$material->name}: {$usedQty} {$unitStr} terpasang" . ($assignedQty > $usedQty ? " (Bekal dibawa: {$assignedQty} {$unitStr}, Sisa dikembalikan: " . ($assignedQty - $usedQty) . " {$unitStr})" : "");
                            $usageDetails[] = $material->name . ' (' . $usedQty . ' ' . $unitStr . ')';
                        }
                        
                        // 2. Jika ada sisa dari bekal, buatkan pengembalian pending ke gudang area
                        if ($usedQty < $assignedQty) {
                            $excessQty = $assignedQty - $usedQty;
                            $returnItems[] = [
                                'material' => $material,
                                'qty' => $excessQty,
                                'unit' => $unitStr
                            ];
                            $rincianNotesReturn[] = "- {$material->name}: SISA (Dikembalikan): {$excessQty} {$unitStr}";
                            $returnFormattedDetails[] = $material->name . ' (' . $excessQty . ' ' . $unitStr . ')';
                        }
                    }
                }

                // Tambahkan ONT ke dalam pemakaian (usedItems) agar terpotong dari Stok Area dan masuk log Riwayat Order
                $customerOnts = $customer->onts()->where('status', 'active')->get();
                foreach ($customerOnts as $cOnt) {
                    if ($cOnt->material_transaction_item_id) {
                        $mItem = \App\Models\MaterialTransactionItem::find($cOnt->material_transaction_item_id);
                        if ($mItem && $mItem->material) {
                            $usedItems[] = [
                                'material' => $mItem->material,
                                'qty' => 1,
                                'unit' => 'pcs',
                                'actual_used' => 1,
                                'assigned_qty' => 1,
                            ];
                            $rincianNotesUsed[] = "- {$mItem->material->name}: 1 pcs (SN: {$cOnt->serial_number})";
                        }
                    }
                }

                // Proses Instalasi (Memotong Stok Area sesuai Pemakaian / Bekal)
                if (count($usedItems) > 0) {
                    // Hapus transaksi lama agar tidak ada riwayat ganda saat edit pemasangan
                    \App\Models\MaterialTransaction::where('customer_id', $customer->id)
                        ->whereIn('type', ['out', 'in'])
                        ->where(function($q) {
                            $q->where('transaction_number', 'like', 'OUT-INSTALASI-%')
                              ->orWhere('transaction_number', 'like', 'RTR-EXCESS-%')
                              ->orWhere('transaction_number', 'like', 'RTR-INS-%');
                        })
                        ->delete();

                    $transactionOut = \App\Models\MaterialTransaction::create([
                        'transaction_number' => 'OUT-INSTALASI-' . date('YmdHis'),
                        'type' => 'out',
                        'status' => 'approved',
                        'date' => now(),
                        'technician_name' => auth()->user()->name,
                        'purpose' => 'Pemakaian Material Instalasi Pelanggan ' . $customer->name,
                        'source_type' => 'instalasi',
                        'source_id' => $customer->id,
                        'source_reference' => $customer->customer_id ?? ('CUST-' . $customer->id),
                        'user_id' => auth()->id(),
                        'customer_id' => $customer->id,
                        'area_id' => $customer->area_id,
                        'notes' => "Rincian Pemakaian Instalasi:\n" . implode("\n", $rincianNotesUsed)
                    ]);

                    $totalCostOut = 0;
                    foreach ($usedItems as $ui) {
                        $materialStock = \App\Models\MaterialStock::where('material_id', $ui['material']->id)
                                        ->where('area_id', $customer->area_id)
                                        ->first();
                        
                        $stockBefore = $materialStock ? $materialStock->stock : 0;
                        $stockAfter = $stockBefore - $ui['qty'];

                        $ppuOut = $ui['material']->selling_price ?: ($ui['material']->price_per_unit ?? 0);
                        if (strtolower($ui['material']->category ?? '') === 'kabel' && strtolower($ui['unit'] ?? '') === 'meter') {
                            $mpr = $ui['material']->meter_per_roll > 0 ? $ui['material']->meter_per_roll : 1000;
                            $ppuOut = $ppuOut / $mpr;
                        }
                        $totalItemPrice = $ppuOut * $ui['qty'];
                        $totalCostOut += $totalItemPrice;

                        \App\Models\MaterialTransactionItem::create([
                            'material_transaction_id' => $transactionOut->id,
                            'material_id' => $ui['material']->id,
                            'quantity' => $ui['qty'],
                            'unit' => $ui['unit'],
                            'price_per_unit' => $ppuOut,
                            'total_price' => $totalItemPrice,
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
                    $transactionOut->update(['total_cost' => $totalCostOut]);
                }

                // Proses Serah Terima Pengembalian Sisa ke Gudang (Verifikasi Fisik oleh Petugas Gudang / Ka. Teknisi)
                if (count($returnItems) > 0) {
                    $returnTrx = \App\Services\MaterialHandoverService::submitReturnHandover(
                        sourceType: 'instalasi',
                        sourceId: $customer->id,
                        sourceRef: $customer->customer_id ?? ('CUST-' . $customer->id),
                        items: array_map(function ($ri) {
                            return [
                                'material_id' => $ri['material']->id,
                                'quantity' => $ri['qty'],
                                'unit' => $ri['unit'],
                                'condition' => 'Layak Pakai',
                            ];
                        }, $returnItems),
                        technicianName: auth()->user()->name,
                        areaId: $customer->area_id,
                        notes: "Pengembalian Sisa Material Instalasi Pelanggan {$customer->name} (" . ($customer->customer_id ?? 'ID:'.$customer->id) . ")\nRincian Hitungan Sisa:\n" . implode("\n", $rincianNotesReturn)
                    );
                }
            }
            
            // Update status jadwal teknisi ke done jika ada dan update notes
            foreach ($schedules as $schedule) {
                $newNotes = $schedule->notes;
                if (count($usageDetails) > 0) {
                    $lines = explode("\n", $newNotes);
                    
                    // Ambil daftar material yang sudah tercatat sebelumnya agar tidak hilang jika tidak diubah
                    $existingMaterials = [];
                    foreach ($lines as $line) {
                        if (str_starts_with(trim($line), 'Material:')) {
                            $mats = explode(',', str_replace('Material:', '', $line));
                            foreach ($mats as $m) {
                                $m = trim($m);
                                if ($m) $existingMaterials[] = $m;
                            }
                        }
                    }

                    $finalMaterials = $usageDetails;
                    foreach ($existingMaterials as $oldMat) {
                        $oldBase = trim(preg_replace('/\s*\(.*?\)/', '', $oldMat));
                        $existsInFinal = false;
                        foreach ($finalMaterials as $finMat) {
                            $finBase = trim(preg_replace('/\s*\(.*?\)/', '', $finMat));
                            if (stripos($finBase, $oldBase) !== false || stripos($oldBase, $finBase) !== false) {
                                $existsInFinal = true;
                                break;
                            }
                        }
                        if (!$existsInFinal) {
                            $finalMaterials[] = $oldMat;
                        }
                    }

                    // Remove old Material and return lines
                    $lines = array_filter($lines, function($line) {
                        $trimmed = trim($line);
                        return !str_starts_with($trimmed, 'Material:') &&
                               !str_starts_with($trimmed, 'Material Terpasang:') &&
                               !str_starts_with($trimmed, 'Material Dikembalikan:') &&
                               !str_starts_with($trimmed, 'Alokasi Awal:') &&
                               !str_starts_with($trimmed, 'Retur Gudang:');
                    });

                    if (!empty($allocatedDetails)) {
                        $lines[] = "Alokasi Awal: " . implode(', ', array_unique($allocatedDetails));
                    }
                    $lines[] = "Material: " . implode(', ', $finalMaterials);
                    if (!empty($returnFormattedDetails)) {
                        $lines[] = "Material Dikembalikan: " . implode(', ', $returnFormattedDetails);
                        if ($returnTrx) {
                            $lines[] = "Retur Gudang: " . $returnTrx->transaction_number;
                        }
                    }
                    $newNotes = implode("\n", $lines);
                }
                $schedule->update([
                    'status' => 'done',
                    'notes' => $newNotes
                ]);
            }
        });
    } catch (\Throwable $e) {
        \Log::error('Error assign-ont: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return back()->withErrors(['error' => 'Gagal menyimpan laporan instalasi: ' . $e->getMessage()]);
    }

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

        if ($customer->status !== 'installing' || !$customer->ont) {
            return redirect()->back()->withErrors(['error' => 'Pelanggan belum memiliki perangkat ONT terpasang atau status bukan dalam proses instalasi.']);
        }

        $customer->update([
            'is_audited' => true,
            'notes' => $validated['notes'] ? ($customer->notes ? $customer->notes . "\n" : '') . "[Audit]: " . $validated['notes'] : $customer->notes,
        ]);

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
            $count = \App\Models\MaterialTransaction::whereDate('created_at', today())->count() + 1;
            $transactionNumber = 'REQ-MAT-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            $materialTransaction = \App\Models\MaterialTransaction::create([
                'transaction_number' => $transactionNumber,
                'customer_id' => $customer->id,
                'user_id' => auth()->id(),
                'technician_name' => auth()->user()->name,
                'area_id' => $customer->area_id,
                'type' => 'out',
                'status' => 'pending',
                'purpose' => 'Request Material Tambahan Pelanggan ' . $customer->name,
                'notes' => $validated['notes'] ?? null,
                'date' => now(),
            ]);

            $totalCostReq = 0;
            foreach ($validated['items'] as $item) {
                $material = \App\Models\Material::find($item['material_id']);
                $ppu = $material ? ($material->selling_price ?: ($material->price_per_unit ?? 0)) : 0;
                $unit = $material ? $material->unit : 'pcs';
                if ($material && strtolower($material->category ?? '') === 'kabel' && strtolower($unit) === 'meter') {
                    $mpr = $material->meter_per_roll > 0 ? $material->meter_per_roll : 1000;
                    $ppu = $ppu / $mpr;
                }
                $itemTotal = $ppu * $item['quantity'];
                $totalCostReq += $itemTotal;

                \App\Models\MaterialTransactionItem::create([
                    'material_transaction_id' => $materialTransaction->id,
                    'material_id' => $item['material_id'],
                    'quantity' => $item['quantity'],
                    'unit' => $unit,
                    'price_per_unit' => $ppu,
                    'total_price' => $itemTotal,
                    'condition' => 'Pengajuan'
                ]);
            }
            $materialTransaction->update(['total_cost' => $totalCostReq]);
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
            'billing_type' => 'nullable|in:prabayar,pascabayar',
            'billing_cycle' => 'nullable|in:default,anniversary,calendar,custom_date',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'is_prorata' => 'nullable|boolean',
            'service_status' => 'nullable|in:berbayar,gratis,free_home',
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
            'enable_tax' => 'nullable|boolean',
            'tax_ppn' => 'nullable|numeric|min:0|max:100',
            'tax_bhp' => 'nullable|numeric|min:0|max:100',
            'tax_uso' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($customer->status === 'installing' && $customer->is_audited && $customer->ont) {
            DB::transaction(function () use ($customer, $validated, $request) {
                $billingType = $validated['billing_type'] ?? $customer->billing_type ?? \App\Models\Setting::get('billing_type', 'prabayar');
                if (!in_array($billingType, ['prabayar', 'pascabayar'])) {
                    $billingType = 'pascabayar';
                }

                $billingCycle = $validated['billing_cycle'] ?? $customer->billing_cycle ?? 'default';
                $billingDay = $validated['billing_day'] ?? $customer->billing_day ?? null;

                $serviceStatus = $validated['service_status'] ?? $customer->service_status ?? 'berbayar';

                $isTaxEnabled = $request->has('enable_tax') ? $request->boolean('enable_tax') : true;

                if (!$isTaxEnabled) {
                    $taxPpn = 0.0;
                    $taxBhp = 0.0;
                    $taxUso = 0.0;
                } else {
                    $taxPpn = (float) ($validated['tax_ppn'] ?? \App\Models\Setting::get('tax_ppn', '0'));
                    $taxBhp = (float) ($validated['tax_bhp'] ?? \App\Models\Setting::get('tax_bhp', '0'));
                    $taxUso = (float) ($validated['tax_uso'] ?? \App\Models\Setting::get('tax_uso', '0'));
                }

                $customer->update([
                    'status' => 'active',
                    'activation_date' => $validated['activation_date'],
                    'billing_type' => $billingType,
                    'billing_cycle' => $billingCycle,
                    'billing_day' => $billingDay,
                    'service_status' => $serviceStatus,
                    'notes' => !empty($validated['notes']) ? ($customer->notes ? $customer->notes . "\n[Aktivasi]: " . $validated['notes'] : $validated['notes']) : $customer->notes,
                    'tax_ppn' => $taxPpn,
                    'tax_bhp' => $taxBhp,
                    'tax_uso' => $taxUso,
                ]);

                $accessMode = $validated['access_mode'] ?? 'PPPOE';
                $isHotspot = in_array(strtoupper($accessMode), ['HOTSPOT', 'HOTSPOT/MEMBER', 'MEMBER']);

                $pppoeUser = $validated['pppoe_user'] ?? null;
                $pppoePassword = $validated['pppoe_password'] ?? null;
                $hotspotUser = $validated['hotspot_user'] ?? null;
                $hotspotPassword = $validated['hotspot_password'] ?? null;

                if ($isHotspot) {
                    if (!empty($pppoeUser)) {
                        $hotspotUser = $pppoeUser;
                        $hotspotPassword = $pppoePassword;
                    }
                }

                $customer->ont->update([
                    'pppoe_user' => $pppoeUser,
                    'pppoe_password' => $pppoePassword,
                    'vlan_mode' => $validated['vlan_mode'] ?? $customer->ont->vlan_mode,
                    'vlan_id' => $validated['vlan_id'] ?? $customer->ont->vlan_id,
                    'access_mode' => $accessMode,
                    'free_hotspot' => !empty($validated['free_hotspot']),
                    'hotspot_user' => $hotspotUser,
                    'hotspot_password' => $hotspotPassword,
                    'hotspot_vlan_id' => $validated['hotspot_vlan_id'] ?? null,
                    'ip_login' => $validated['ip_login'] ?? $customer->ont->ip_login,
                    'login_user' => $validated['login_user'] ?? $customer->ont->login_user,
                    'login_password' => $validated['login_password'] ?? $customer->ont->login_password,
                ]);

                // Create initial invoice
                $activationDate = \Carbon\Carbon::parse($validated['activation_date']);
                $basePrice = $customer->base_amount > 0 ? (float) $customer->base_amount : ($customer->package ? (float) $customer->package->price : 0);
                $amount = $basePrice;
                $isProrata = false;

                $prorataFormula = \App\Models\Setting::get('prorata_formula', 'exact_days');
                $issueDateSetting = (int) \App\Models\Setting::get('invoice_issue_date', '1');

                $isProrataFirstMonth = $request->has('is_prorata') 
                    ? $request->boolean('is_prorata') 
                    : (\App\Models\Setting::get('prorata_first_month', '1') === '1');
                $invoiceBillingType = $billingType;

                if ($billingType === 'pascabayar') {
                    // Pascabayar: Hitung tanggal cut-off / terbit berikutnya
                    $nextBillingDate = $activationDate->copy();
                    if ($activationDate->day >= $issueDateSetting) {
                        $nextBillingDate->startOfMonth()->addMonthNoOverflow()->day($issueDateSetting);
                    } else {
                        $nextBillingDate->day($issueDateSetting);
                    }

                    if ($isProrataFirstMonth) {
                        // Tagihan pertama dihitung prorata sampai akhir bulan berjalan
                        $endOfMonth = $activationDate->copy()->endOfMonth();
                        $totalDaysInCycle = (int) $activationDate->daysInMonth;
                        $daysUsed = (int) ($endOfMonth->day - $activationDate->day + 1);

                        if ($amount > 0 && $daysUsed > 0 && $totalDaysInCycle > 0) {
                            if ($daysUsed < $totalDaysInCycle) {
                                if ($prorataFormula === 'fixed_30') {
                                    $amount = ($amount / 30) * $daysUsed;
                                } elseif ($prorataFormula === 'mid_month') {
                                    if ($activationDate->day > 15) {
                                        $amount = $amount / 2;
                                    }
                                } else {
                                    // default: exact_days
                                    $amount = ($amount / $totalDaysInCycle) * $daysUsed;
                                }
                                $amount = (int) round($amount);
                            }
                        }

                        $isProrata = true;
                        $invoiceBillingType = 'prorata';
                        $invoiceNotes = 'Tagihan Pertama (Prorata s/d Akhir Bulan: ' . $activationDate->format('d M') . ' - ' . $endOfMonth->format('d M Y') . ')';
                    } else {
                        // Pascabayar normal 1 bulan penuh
                        $invoiceBillingType = 'pascabayar';
                        $invoiceNotes = 'Tagihan Pertama (Pascabayar)';
                    }
                } else {
                    // Prabayar
                    if ($isProrataFirstMonth) {
                        $endOfMonth = $activationDate->copy()->endOfMonth();
                        $totalDaysInCycle = (int) $activationDate->daysInMonth;
                        $daysUsed = (int) ($endOfMonth->day - $activationDate->day + 1);

                        if ($amount > 0 && $daysUsed > 0 && $totalDaysInCycle > 0) {
                            if ($daysUsed < $totalDaysInCycle) {
                                if ($prorataFormula === 'fixed_30') {
                                    $amount = ($amount / 30) * $daysUsed;
                                } elseif ($prorataFormula === 'mid_month') {
                                    if ($activationDate->day > 15) {
                                        $amount = $amount / 2;
                                    }
                                } else {
                                    $amount = ($amount / $totalDaysInCycle) * $daysUsed;
                                }
                                $amount = (int) round($amount);
                            }
                        }

                        $isProrata = true;
                        $invoiceBillingType = 'prorata';
                        $invoiceNotes = 'Tagihan Pertama (Prorata s/d Akhir Bulan: ' . $activationDate->format('d M') . ' - ' . $endOfMonth->format('d M Y') . ')';
                    } else {
                        $invoiceBillingType = 'prabayar';
                        $invoiceNotes = 'Tagihan Pertama (Prabayar)';
                    }
                }
                
                $invoiceIssuedDate = $billingType === 'pascabayar' ? $nextBillingDate : $activationDate;
                $dueDateTime = app(\App\Services\BillingIsolationSchedule::class)->forIssueDate($invoiceIssuedDate)
                    ->setTimezone(config('billing.timezone', 'Asia/Jakarta'));

                // JANGAN BUAT INVOICE APAPUN JIKA PELANGGAN GRATIS ATAU FREE HOME
                if (!in_array($serviceStatus, ['gratis', 'free_home'])) {
                    if ($taxPpn > 0) {
                        $amount = $amount + ($amount * ($taxPpn / 100));
                    }
                    $amount = (int) round($amount);

                    \App\Models\Invoice::create([
                        'customer_id' => $customer->id,
                        'period_month' => $activationDate->month,
                        'period_year' => $activationDate->year,
                        'amount' => $amount,
                        'due_date' => $dueDateTime,
                        'issued_date' => $invoiceIssuedDate,
                        'status' => 'unpaid',
                        'is_prorata' => $isProrata,
                        'billing_type' => $invoiceBillingType,
                        'billing_cycle' => $billingCycle === 'default' ? \App\Models\Setting::get('billing_cycle', 'anniversary') : $billingCycle,
                        'notes' => $invoiceNotes,
                    ]);
                }

                // =========================================================================
                // AUTO INCENTIVES UPON ACTIVATION: Fee Booking, Fee Survey, Fee Instalasi
                // =========================================================================
                $codeLabel = ($customer->customer_code && !preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/i', $customer->customer_code))
                    ? " ({$customer->customer_code})"
                    : '';

                $isOperationalFeeEligible = \App\Services\AffiliateService::isOperationalFeeEligible($customer);

                // 1. Fee Booking (Sales / Referral)
                if ($customer->sales_id) {
                    $salesUser = \App\Models\User::find($customer->sales_id);
                    if ($salesUser) {
                        $isEmployee = \App\Services\AffiliateService::isInternalEmployee($salesUser);

                        // Fee booking hanya berlaku untuk booking baru sejak 1 Oktober 2026.
                        if ($isOperationalFeeEligible && $isEmployee) {
                            $masterBooking = (float) (\App\Models\MasterFee::where('type', 'Fee Booking')->where('is_active', true)->value('nominal') ?? 35000);
                            $bookingAmount = ($salesUser->booking_fee && (float)$salesUser->booking_fee > 0)
                                ? (float) $salesUser->booking_fee
                                : $masterBooking;

                            if ($bookingAmount > 0) {
                                $bookingRef = 'booking_cust_' . $customer->id;
                                $alreadyBooking = \App\Models\Incentive::where('reference_id', $bookingRef)
                                    ->orWhere(function($sub) use ($customer, $salesUser) {
                                        $sub->where('user_id', $salesUser->id)
                                            ->where('type', 'auto')
                                            ->where('description', 'like', 'Fee Booking Pelanggan:%')
                                            ->where(function($q) use ($customer) {
                                                $q->where('description', 'like', '%' . $customer->name . '%')
                                                  ->orWhere('description', 'like', '%' . ($customer->customer_code ?? '---') . '%');
                                            });
                                    })
                                    ->exists();

                                if (!$alreadyBooking) {
                                    \App\Models\Incentive::create([
                                        'user_id' => $salesUser->id,
                                        'amount' => $bookingAmount,
                                        'type' => 'auto',
                                        'description' => "Fee Booking Pelanggan: {$customer->name}{$codeLabel}",
                                        'reference_id' => $bookingRef,
                                        'incentive_date' => now(),
                                        'status' => 'pending',
                                    ]);
                                }
                            }
                        }

                        // Catat juga di AffiliateCommission untuk modul afiliasi.
                        if ($isOperationalFeeEligible && !in_array($customer->service_status, ['gratis', 'free_home'])) {
                            try {
                                \App\Services\AffiliateService::recordBookingCommission($customer);
                            } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::warning('[Affiliate] Activation booking fee error: ' . $e->getMessage());
                            }
                        }
                    }
                }

                // 2. Fee Survey (Surveyor / Teknisi Survey)
                if ($isOperationalFeeEligible) {
                    $surveyorIds = collect();
                    $allSurveys = $customer->surveys()->get();
                    foreach ($allSurveys as $survey) {
                        if ($survey->surveyor_id) {
                            $surveyorIds->push($survey->surveyor_id);
                        }
                    }
                    $surveySchedules = $customer->technicianSchedules()
                        ->where('type', 'survey')
                        ->get();
                    foreach ($surveySchedules as $sched) {
                        if ($sched->technician_id) {
                            $surveyorIds->push($sched->technician_id);
                        }
                    }
                    $surveyorIds = $surveyorIds->unique()->filter()->values();
                    $masterFeeSurvey = (float) (\App\Models\MasterFee::where('type', 'Fee Survey')->where('is_active', true)->value('nominal') ?? 15000);

                    foreach ($surveyorIds as $srvId) {
                        $surveyorUser = \App\Models\User::find($srvId);
                        if (!$surveyorUser) continue;

                        if ($masterFeeSurvey > 0) {
                            $surveyRef = 'survey_cust_' . $customer->id . '_user_' . $surveyorUser->id;
                            $alreadySurvey = \App\Models\Incentive::where('reference_id', $surveyRef)
                                ->orWhere(function($sub) use ($customer, $surveyorUser) {
                                    $sub->where('user_id', $surveyorUser->id)
                                        ->where('type', 'auto')
                                        ->where('description', 'like', 'Fee Survey Pelanggan:%')
                                        ->where(function($q) use ($customer) {
                                            $q->where('description', 'like', '%' . $customer->name . '%')
                                              ->orWhere('description', 'like', '%' . ($customer->customer_code ?? '---') . '%');
                                        });
                                })
                                ->exists();

                            if (!$alreadySurvey) {
                                \App\Models\Incentive::create([
                                    'user_id' => $surveyorUser->id,
                                    'amount' => $masterFeeSurvey,
                                    'type' => 'auto',
                                    'description' => "Fee Survey Pelanggan: {$customer->name}{$codeLabel}",
                                    'reference_id' => $surveyRef,
                                    'incentive_date' => now(),
                                    'status' => 'pending',
                                ]);
                            }
                        }
                    }
                }

                // Selesaikan status seluruh jadwal teknisi survey
                $customer->technicianSchedules()
                    ->where('type', 'survey')
                    ->whereIn('status', ['scheduled', 'on_the_way', 'working'])
                    ->update(['status' => 'done']);

                // 3. Fee Pasang / Instalasi (Setiap Teknisi yang bertugas mendapatkan insentif penuh masing-masing)
                if ($isOperationalFeeEligible) {
                    $installSchedules = $customer->technicianSchedules()
                        ->where('type', 'installation')
                        ->whereIn('status', ['scheduled', 'on_the_way', 'working', 'done'])
                        ->get();

                    if ($installSchedules->isEmpty()) {
                        $installSchedules = $customer->technicianSchedules()
                            ->where('type', 'installation')
                            ->get();
                    }

                    $technicianIds = $installSchedules->pluck('technician_id')->unique()->filter()->values();
                    $masterFeePasang = (float) (\App\Models\MasterFee::where('type', 'Fee Pasang')->where('is_active', true)->value('nominal') ?? 35000);

                    foreach ($technicianIds as $techId) {
                        $techUser = \App\Models\User::find($techId);
                        if (!$techUser) continue;

                        // Nominal tarif insentif: custom per teknisi jika ada (> 0), jika tidak gunakan Master Fee Pasang (Rp 35.000)
                        $feeAmount = ($techUser->installation_fee && (float)$techUser->installation_fee > 0)
                            ? (float) $techUser->installation_fee
                            : $masterFeePasang;

                        if ($feeAmount > 0) {
                            $installRef = 'install_cust_' . $customer->id . '_user_' . $techUser->id;
                            $alreadyExists = \App\Models\Incentive::where('reference_id', $installRef)
                                ->orWhere(function($sub) use ($customer, $techUser) {
                                    $sub->where('user_id', $techUser->id)
                                        ->where('type', 'auto')
                                        ->where('description', 'like', 'Fee Pasang Pelanggan:%')
                                        ->where(function($q) use ($customer) {
                                            $q->where('description', 'like', '%' . $customer->name . '%')
                                              ->orWhere('description', 'like', '%' . ($customer->customer_code ?? '---') . '%');
                                        });
                                })
                                ->exists();

                            if (!$alreadyExists) {
                                \App\Models\Incentive::create([
                                    'user_id' => $techUser->id,
                                    'amount' => $feeAmount,
                                    'type' => 'auto',
                                    'description' => "Fee Pasang Pelanggan: {$customer->name}{$codeLabel}",
                                    'reference_id' => $installRef,
                                    'incentive_date' => now(),
                                    'status' => 'pending'
                                ]);
                            }
                        }
                    }
                }

                // Selesaikan status seluruh jadwal teknisi instalasi untuk pelanggan ini
                $customer->technicianSchedules()
                    ->where('type', 'installation')
                    ->whereIn('status', ['scheduled', 'on_the_way', 'working'])
                    ->update(['status' => 'done']);
            });

            // Sinkronisasi RADIUS PPPoE (dilewati jika mode free_home voucher ONT)
            if ($customer->service_status !== 'free_home') {
                try {
                    app(\App\Services\RadiusService::class)->syncCustomer($customer);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[RADIUS] Activation sync error: ' . $e->getMessage());
                }
            }

            // Hitung dan catat Bonus Target Market jika ada program aktif
            try {
                \App\Services\TargetMarketService::processCustomerActivation($customer);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[TargetMarket] Bonus calculation error: ' . $e->getMessage());
            }

            \App\Models\AuditLog::createLog('Aktivasi Layanan', $customer, 'installing', 'active', 'Mengaktifkan layanan pelanggan');

            return redirect()->back()->with('success', 'Pelanggan berhasil diaktivasi!');
        }
        return redirect()->back()->with('error', 'Pelanggan belum siap diaktivasi.');
    }

    public function updateOntInline(Request $request, Customer $customer)
    {
        // Check if there are any pending material requests
        $pendingRequest = \App\Models\MaterialTransaction::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->where(function($q) {
                $q->where('purpose', 'like', '%Request%')
                  ->orWhere('transaction_number', 'like', 'REQ-%');
            })
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
            try {
                app(\App\Services\RadiusService::class)->syncCustomer($customer);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[RADIUS] ONT update sync error: ' . $e->getMessage());
            }
            return back()->with('success', 'Data konfigurasi ONT berhasil diperbarui.');
        }
        
        return back()->with('error', 'Data ONT tidak ditemukan.');
    }

    /**
     * 1. Update Data Booking & Lokasi Inline
     */
    public function updateBookingInline(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customer->id),
                function ($attribute, $value, $fail) use ($customer) {
                    $userExists = \App\Models\User::where('email', $value)
                        ->when($customer->user_id, fn($q) => $q->where('id', '!=', $customer->user_id))
                        ->exists();
                    if ($userExists) {
                        $fail('Email ini sudah digunakan oleh akun login sistem lain.');
                    }
                }
            ],
            'latitude' => 'nullable|string|max:50',
            'longitude' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'registration_date' => 'nullable|date',
            'sales_id' => 'nullable|exists:users,id',
            'package_id' => 'nullable|exists:internet_packages,id',
            'area_id' => 'nullable|exists:areas,id',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
        ], [
            'email.unique' => 'Email ini sudah terdaftar pada pelanggan lain. Silakan gunakan email berbeda.',
            'email.email' => 'Format penulisan email tidak valid.',
        ]);

        $user = auth()->user();
        $userRole = strtolower(trim($user?->role ?? ''));
        $isSuperAdmin = in_array($userRole, ['admin', 'super admin', 'superadmin', 'super-admin']) 
            || ($user && method_exists($user, 'hasRole') && ($user->hasRole('super admin') || $user->hasRole('admin')))
            || ($user && $user->id === 1);

        if (!$isSuperAdmin) {
            unset($validated['sales_id']);
        } else {
            $validated['sales_id'] = !empty($validated['sales_id']) ? $validated['sales_id'] : null;
        }

        if (!empty($validated['area_id'])) {
            $area = \App\Models\Area::find($validated['area_id']);
            if ($area) {
                $validated['area'] = $area->name;
            }
        }

        $oldArea = $customer->areaModel?->name ?? $customer->area ?? '-';
        $customer->update($validated);

        if ($customer->user) {
            $userUpdate = [];
            if (!empty($validated['name'])) $userUpdate['name'] = $validated['name'];
            if (!empty($validated['email'])) $userUpdate['email'] = $validated['email'];
            if (!empty($validated['area_id'])) $userUpdate['area_id'] = $validated['area_id'];
            if (!empty($userUpdate)) {
                $customer->user->update($userUpdate);
            }
        }

        $logDesc = 'Memperbarui data booking, lokasi, & sales';
        if ($customer->wasChanged('area_id') || $customer->wasChanged('area')) {
            $newArea = $customer->areaModel?->name ?? $customer->area ?? '-';
            $logDesc .= " (Area berpindah: {$oldArea} -> {$newArea}, otomatis sinkron ke perangkat, jadwal teknisi, surat jalan material, survey, tiket, & user)";
        }

        \App\Models\AuditLog::createLog('Edit Booking & Lokasi', $customer, $customer->status, $customer->status, $logDesc);

        return back()->with('success', 'Data booking & lokasi berhasil diperbarui.');
    }

    /**
     * 2. Update Jadwal Survey Inline
     */
    public function updateSurveySchedule(Request $request, Customer $customer): RedirectResponse
    {
        $techIds = $request->input('technician_ids', []);
        if (empty($techIds) && $request->filled('technician_id')) {
            $techIds = [$request->input('technician_id')];
        }

        $validated = $request->validate([
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
            'technician_id' => 'nullable|exists:users,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|string',
            'status' => 'required|in:scheduled,on_the_way,working,done,cancelled',
            'notes' => 'nullable|string',
        ]);

        if (empty($techIds) && !empty($validated['technician_id'])) {
            $techIds = [$validated['technician_id']];
        }

        // Normalisasi format jam HH:mm jika dari input time HTML
        if (!empty($validated['scheduled_time']) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $validated['scheduled_time'])) {
            $validated['scheduled_time'] = substr($validated['scheduled_time'], 0, 5);
        }

        // Hapus jadwal survey sebelumnya agar penugasan akurat sesuai petugas terpilih
        $customer->technicianSchedules()
            ->where('type', 'survey')
            ->whereIn('status', ['scheduled', 'on_the_way', 'working', 'rescheduled'])
            ->delete();

        foreach ($techIds as $tId) {
            \App\Models\TechnicianSchedule::create([
                'customer_id' => $customer->id,
                'technician_id' => $tId,
                'type' => 'survey',
                'status' => $validated['status'],
                'scheduled_date' => $validated['scheduled_date'],
                'scheduled_time' => $validated['scheduled_time'] ?: null,
                'notes' => $validated['notes'] ?: 'Jadwal survey',
                'area_id' => $customer->area_id,
            ]);
        }

        \App\Models\AuditLog::createLog('Edit Jadwal Survey', $customer, $customer->status, $customer->status, 'Memperbarui jadwal survey teknisi');

        return back()->with('success', 'Jadwal survey berhasil disimpan.');
    }

    /**
     * 3. Update Laporan Hasil Survey Inline
     */
    public function updateSurveyReport(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'surveyor_id' => 'required|exists:users,id',
            'survey_date' => 'required|date',
            'odp_id' => 'nullable|exists:odps,id',
            'port_number' => 'nullable|integer',
            'distance_meters' => 'nullable|numeric',
            'signal_loss' => 'nullable|numeric',
            'port_available' => 'nullable|boolean',
            'feasibility' => 'required|in:feasible,not_feasible,conditional',
            'notes' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*.label' => 'nullable|string',
            'photos.*.file' => 'nullable|image|max:10240',
        ]);

        $survey = $customer->surveys()->first();

        $photoPaths = $survey ? ($survey->photos ?? []) : [];
        if ($request->has('photos') && is_array($request->photos)) {
            foreach ($request->photos as $idx => $photoItem) {
                if (isset($photoItem['file']) && $photoItem['file'] instanceof \Illuminate\Http\UploadedFile) {
                    $path = $photoItem['file']->store('surveys', 'public');
                    $photoPaths[] = [
                        'label' => $photoItem['label'] ?? ('Foto ' . ($idx + 1)),
                        'path' => $path,
                    ];
                }
            }
        }

        $payload = [
            'surveyor_id' => $validated['surveyor_id'],
            'survey_date' => $validated['survey_date'],
            'odp_id' => $validated['odp_id'] ?? null,
            'port_number' => $validated['port_number'] ?? null,
            'distance_meters' => $validated['distance_meters'] ?? null,
            'signal_loss' => $validated['signal_loss'] ?? null,
            'port_available' => $request->boolean('port_available', false),
            'feasibility' => $validated['feasibility'],
            'notes' => $validated['notes'] ?? null,
            'photos' => !empty($photoPaths) ? $photoPaths : null,
        ];

        if ($survey) {
            $survey->update($payload);
        } else {
            $payload['customer_id'] = $customer->id;
            \App\Models\Survey::create($payload);
        }

        \App\Models\AuditLog::createLog('Edit Laporan Survey', $customer, $customer->status, $customer->status, 'Memperbarui hasil laporan survey');

        return back()->with('success', 'Hasil laporan survey berhasil disimpan.');
    }

    /**
     * 4. Update Jadwal Pasang Inline
     */
    public function updateInstallationSchedule(Request $request, Customer $customer): RedirectResponse
    {
        $techIds = $request->input('technician_ids', []);
        if (empty($techIds) && $request->filled('technician_id')) {
            $techIds = [$request->input('technician_id')];
        }

        $validated = $request->validate([
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
            'technician_id' => 'nullable|exists:users,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable|string',
            'status' => 'required|in:scheduled,on_the_way,working,done,cancelled',
            'notes' => 'nullable|string',
            'ont_id' => 'nullable',
            'material_items' => 'nullable|array',
            'material_items.*.name' => 'nullable|string',
            'material_items.*.qty' => 'nullable|numeric',
            'material_items.*.unit' => 'nullable|string',
            'material_items.*.trx_number' => 'nullable|string',
            'send_wa' => 'nullable|boolean',
        ]);

        if (empty($techIds) && !empty($validated['technician_id'])) {
            $techIds = [$validated['technician_id']];
        }

        if (!empty($validated['scheduled_time']) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $validated['scheduled_time'])) {
            $validated['scheduled_time'] = substr($validated['scheduled_time'], 0, 5);
        }

        $customNotes = [];

        // 1. Link / Update ONT jika dipilih
        if (!empty($validated['ont_id'])) {
            $ont = Ont::find($validated['ont_id']);
            if ($ont) {
                $ont->update([
                    'customer_id' => $customer->id,
                ]);
                $name = $ont->brand . ($ont->model ? ' ' . $ont->model : '') . ' (SN: ' . $ont->serial_number . ')';
                $customNotes[] = "ONT: " . $name;
            }
        }

        // 2. Format Material Items
        if (!empty($validated['material_items'])) {
            $matLines = [];
            $trxNumbers = [];
            foreach ($validated['material_items'] as $m) {
                if (!empty($m['name']) && !empty($m['qty'])) {
                    $matLines[] = "Material: " . $m['name'] . " (" . $m['qty'] . " " . ($m['unit'] ?? 'pcs') . ")";
                    if (!empty($m['trx_number'])) {
                        $trxNumbers[] = $m['trx_number'];
                    }
                }
            }
            if (!empty($trxNumbers)) {
                $customNotes[] = "Material diambil dari Surat Jalan / Order: " . implode(', ', array_unique($trxNumbers));
            }
            if (!empty($matLines)) {
                $customNotes = array_merge($customNotes, $matLines);
            }
        }

        if (!empty($validated['notes'])) {
            $customNotes[] = "Catatan Khusus: " . $validated['notes'];
        }

        $finalNotes = !empty($customNotes) ? implode("\n", $customNotes) : ($validated['notes'] ?: 'Jadwal pasang');

        // Hapus jadwal instalasi sebelumnya agar penugasan akurat sesuai petugas terpilih
        $customer->technicianSchedules()
            ->where('type', 'installation')
            ->whereIn('status', ['scheduled', 'on_the_way', 'working', 'rescheduled'])
            ->delete();

        foreach ($techIds as $tId) {
            \App\Models\TechnicianSchedule::create([
                'customer_id' => $customer->id,
                'technician_id' => $tId,
                'type' => 'installation',
                'status' => $validated['status'],
                'scheduled_date' => $validated['scheduled_date'],
                'scheduled_time' => $validated['scheduled_time'] ?: null,
                'notes' => $finalNotes,
                'area_id' => $customer->area_id,
            ]);
        }

        // Kirim notifikasi WA ke teknisi jika diminta
        if ($request->boolean('send_wa', false)) {
            try {
                $waService = app(\App\Services\WhatsAppGatewayService::class);
                $technicians = User::whereIn('id', $techIds)->get();
                foreach ($technicians as $tech) {
                    if (!empty($tech->phone)) {
                        $msg = $this->formatInstallationWaMessage(
                            $customer,
                            $tech,
                            $validated['scheduled_date'],
                            $validated['scheduled_time'] ?? '10:00',
                            $finalNotes
                        );
                        $waService->sendMessage($tech->phone, $msg);
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('[updateInstallationSchedule] WA Error: ' . $e->getMessage());
            }
        }

        \App\Models\AuditLog::createLog('Edit Jadwal Pasang', $customer, $customer->status, $customer->status, 'Memperbarui jadwal instalasi, perangkat ONT & alokasi material');

        return back()->with('success', 'Jadwal pasang & alokasi material berhasil disimpan.');
    }

    /**
     * 5. Update Laporan Instalasi & ONT Inline
     */
    public function updateInstallationReport(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'odp_id' => 'required|exists:odps,id',
            'port_number' => 'required|integer',
            'serial_number' => 'required|string',
            'mac_address' => 'nullable|string',
            'model' => 'nullable|string',
            'rx_power' => 'nullable|string',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'photo_odp' => 'nullable|image|max:10240',
            'photo_installation' => 'nullable|image|max:10240',
            'photo_ont' => 'nullable|image|max:10240',
            'photo_customer' => 'nullable|image|max:10240',
            'photo_redaman' => 'nullable|image|max:10240',
        ]);

        $cleanMac = !empty($validated['mac_address']) 
            ? \App\Services\DataMigrationService::sanitizeMacAddress($validated['mac_address']) 
            : null;

        $cleanRx = !empty($validated['rx_power'])
            ? \App\Services\DataMigrationService::parseRxPower($validated['rx_power'])
            : null;

        $ont = $customer->ont;
        if (!$ont) {
            $ont = \App\Models\Ont::where('serial_number', $validated['serial_number'])->first();
            if ($ont) {
                $ont->update(['customer_id' => $customer->id]);
            } else {
                $ont = \App\Models\Ont::create([
                    'customer_id' => $customer->id,
                    'serial_number' => $validated['serial_number'],
                    'mac_address' => $cleanMac,
                    'odp_id' => $validated['odp_id'],
                    'port_number' => $validated['port_number'],
                    'model' => $validated['model'] ?? 'Standard ONT',
                    'status' => 'active',
                ]);
            }
        }

        $photoUpdates = [];
        foreach (['photo_odp', 'photo_installation', 'photo_ont', 'photo_customer', 'photo_redaman'] as $field) {
            if ($request->hasFile($field)) {
                $photoUpdates[$field] = $request->file($field)->store('ont_installations', 'public');
            }
        }

        $ont->update(array_merge([
            'odp_id' => $validated['odp_id'],
            'port_number' => $validated['port_number'],
            'serial_number' => $validated['serial_number'],
            'mac_address' => $cleanMac ?: $ont->mac_address,
            'model' => $validated['model'] ?: $ont->model,
            'rx_power' => $cleanRx !== null ? $cleanRx : $ont->rx_power,
            'start_time' => $validated['start_time'] ?? $ont->start_time,
            'end_time' => $validated['end_time'] ?? $ont->end_time,
        ], $photoUpdates));

        // Mark port as used
        \App\Models\OdpPort::updateOrCreate(
            ['odp_id' => $validated['odp_id'], 'port_number' => $validated['port_number']],
            ['status' => 'used']
        );

        \App\Models\AuditLog::createLog('Edit Laporan Instalasi', $customer, $customer->status, $customer->status, 'Memperbarui laporan instalasi & ONT');

        return back()->with('success', 'Laporan instalasi berhasil disimpan.');
    }

    /**
     * 6. Update Aktivasi & Konfigurasi Layanan Inline
     */
    public function updateActivation(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'activation_date' => 'required|date',
            'service_status' => 'required|in:berbayar,gratis,free_home',
            'billing_type' => 'required|in:prabayar,pascabayar',
            'billing_cycle' => 'nullable|in:default,anniversary,calendar,custom_date',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'package_id' => 'nullable|exists:internet_packages,id',
            'pppoe_user' => 'nullable|string',
            'pppoe_password' => 'nullable|string',
            'vlan_mode' => 'nullable|string',
            'vlan_id' => 'nullable|string',
            'access_mode' => 'nullable|string',
            'ip_login' => 'nullable|string',
            'login_user' => 'nullable|string',
            'login_password' => 'nullable|string',
            'free_hotspot' => 'nullable|boolean',
            'hotspot_user' => 'nullable|string',
            'hotspot_password' => 'nullable|string',
            'hotspot_vlan_id' => 'nullable|string',
        ]);

        $custUpdates = [
            'activation_date' => $validated['activation_date'],
            'service_status' => $validated['service_status'],
            'billing_type' => $validated['billing_type'],
            'billing_cycle' => $validated['billing_cycle'] ?? 'default',
            'billing_day' => $validated['billing_day'] ?? null,
        ];
        if (!empty($validated['package_id'])) {
            $custUpdates['package_id'] = $validated['package_id'];
            $pkg = \App\Models\InternetPackage::find($validated['package_id']);
            if ($pkg) {
                $custUpdates['base_amount'] = $pkg->price;
            }
        }
        $customer->update($custUpdates);

        $ont = $customer->ont ?: \App\Models\Ont::where('customer_id', $customer->id)->first();
        if (!$ont && !empty($customer->ont_id)) {
            $ont = \App\Models\Ont::find($customer->ont_id);
            if ($ont) {
                $ont->update(['customer_id' => $customer->id]);
            }
        }

        if ($ont) {
            $accessMode = $validated['access_mode'] ?? 'PPPOE';
            $isHotspot = in_array(strtoupper($accessMode), ['HOTSPOT', 'HOTSPOT/MEMBER', 'MEMBER']);

            $pppoeUser = $validated['pppoe_user'] ?? null;
            $pppoePassword = $validated['pppoe_password'] ?? null;
            $hotspotUser = $validated['hotspot_user'] ?? null;
            $hotspotPassword = $validated['hotspot_password'] ?? null;

            if ($isHotspot) {
                if (!empty($pppoeUser)) {
                    $hotspotUser = $pppoeUser;
                    $hotspotPassword = $pppoePassword;
                }
            } else if (strtoupper($accessMode) === 'PPPOE') {
                if (empty($pppoeUser) && !empty($hotspotUser)) {
                    $pppoeUser = $hotspotUser;
                    $pppoePassword = $hotspotPassword;
                }
            }

            $ont->update([
                'pppoe_user' => $pppoeUser,
                'pppoe_password' => $pppoePassword,
                'vlan_mode' => $validated['vlan_mode'],
                'vlan_id' => $validated['vlan_id'],
                'access_mode' => $accessMode,
                'ip_login' => $validated['ip_login'],
                'login_user' => $validated['login_user'],
                'login_password' => $validated['login_password'],
                'free_hotspot' => $request->boolean('free_hotspot', false),
                'hotspot_user' => $hotspotUser,
                'hotspot_password' => $hotspotPassword,
                'hotspot_vlan_id' => $validated['hotspot_vlan_id'],
            ]);

            try {
                app(\App\Services\RadiusService::class)->syncCustomer($customer);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[RADIUS] Activation sync error: ' . $e->getMessage());
            }
        }


        \App\Models\AuditLog::createLog('Edit Konfigurasi Aktivasi', $customer, $customer->status, $customer->status, 'Memperbarui data aktivasi & konfigurasi layanan');

        return back()->with('success', 'Data aktivasi & konfigurasi layanan berhasil disimpan.');
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

        // Hapus jadwal survey yang masih scheduled sebelumnya agar tidak ada duplikasi atau teknisi lama tertinggal
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
                'notes' => $validated['notes'],
                'area_id' => $customer->area_id,
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
            'send_wa' => 'nullable|boolean',
        ]);

        \Log::info('assignInstall validated OK', ['customer_id' => $customer->id]);

        $customNotes = [];
        $ontsToUpdate = [];
        $transactionsToUpdate = [];

        // 1. VALIDASI ONT
        if (!empty($validated['ont_models'])) {
            $ontIds = array_filter($validated['ont_models']);
            if (count($ontIds) > 0) {
                $ontsToUpdate = Ont::whereIn('id', $ontIds)->get();
                
                // VALIDASI AREA ONT
                $customerAreaId = $customer->area_id ?: \App\Models\Area::where('name', $customer->area)->value('id');
                if (!$customer->area_id && $customerAreaId) {
                    $customer->update(['area_id' => $customerAreaId]);
                }
                if ($customerAreaId) {
                    foreach ($ontsToUpdate as $ont) {
                        if ($ont->area_id && (int) $ont->area_id !== (int) $customerAreaId) {
                            return redirect()->back()->withErrors(['ont_models' => 'ONT ' . $ont->serial_number . ' tidak sesuai dengan Area/Wilayah pelanggan.'])->with('error', 'Data ONT/Material tidak sesuai dengan Area/Wilayah pelanggan.');
                        }
                    }
                }
            }
        }

        // 2. VALIDASI MATERIAL TRANSACTION
        if (!empty($validated['material_transaction_ids'])) {
            $trxs = array_filter($validated['material_transaction_ids']);
            if (count($trxs) > 0) {
                $transactionsToUpdate = \App\Models\MaterialTransaction::whereIn('transaction_number', $trxs)->get();
                // VALIDASI AREA MATERIAL TRANSACTION
                $customerAreaId = $customer->area_id ?: \App\Models\Area::where('name', $customer->area)->value('id');
                if ($customerAreaId) {
                    foreach ($transactionsToUpdate as $trx) {
                        if ($trx->area_id && (int) $trx->area_id !== (int) $customerAreaId) {
                            return redirect()->back()->withErrors(['material_transaction_ids' => 'Material/Surat Jalan ' . $trx->transaction_number . ' tidak sesuai dengan Area/Wilayah pelanggan.'])->with('error', 'Data ONT/Material tidak sesuai dengan Area/Wilayah pelanggan.');
                        }
                    }
                }
            }
        }

        // 3. EXECUTE UPDATES
        $finalNotes = '';
        DB::transaction(function () use ($ontsToUpdate, $transactionsToUpdate, $validated, $customer, &$customNotes, &$finalNotes) {
            
            // Update ONT
            if (count($ontsToUpdate) > 0) {
                $ontNames = [];
                foreach ($ontsToUpdate as $ont) {
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
                    
                    $name = $ont->brand;
                    if ($ont->model) $name .= ' ' . $ont->model;
                    $name .= ' (SN: ' . $ont->serial_number . ')';
                    $ontNames[] = $name;
                }
                if (count($ontNames) > 0) {
                    $customNotes[] = "ONT: " . implode(', ', $ontNames);
                }
            }

            // Material Transaction Notes
            if (count($transactionsToUpdate) > 0) {
                $trxs = array_filter($validated['material_transaction_ids']);
                $trxNotes = ["Material diambil dari Surat Jalan / Order: " . implode(', ', $trxs)];
                if (!empty($validated['material_items'])) {
                    foreach ($validated['material_items'] as $mItem) {
                        $trxNotes[] = "Material: " . $mItem['name'] . " (" . $mItem['qty'] . " " . $mItem['unit'] . ")";
                    }
                }
                $customNotes[] = implode("\n", $trxNotes);
            }

            if (!empty($validated['notes'])) {
                $customNotes[] = "Catatan Tambahan: " . $validated['notes'];
            }
            $finalNotes = implode("\n\n", $customNotes);

            // Hapus jadwal instalasi sebelumnya agar penugasan baru akurat & menggantikan teknisi lama
            $customer->technicianSchedules()
                ->where('type', 'installation')
                ->whereIn('status', ['scheduled', 'on_the_way', 'working', 'rescheduled'])
                ->delete();

            // Create Technician Schedules
            foreach ($validated['technician_ids'] as $techId) {
                TechnicianSchedule::create([
                    'customer_id' => $customer->id,
                    'technician_id' => $techId,
                    'scheduled_date' => $validated['scheduled_date'],
                    'scheduled_time' => $validated['scheduled_time'],
                    'type' => 'installation',
                    'status' => 'scheduled',
                    'notes' => $finalNotes,
                    'area_id' => $customer->area_id,
                ]);
            }

            \App\Models\AuditLog::createLog('Jadwal Instalasi', $customer, 'installing', 'installing', 'Menjadwalkan pemasangan');
        });

        // 4. Kirim Notifikasi WhatsApp Otomatis ke Teknisi jika diminta
        $waNotifiedCount = 0;
        if ($request->boolean('send_wa', true)) {
            try {
                $waService = app(\App\Services\WhatsAppGatewayService::class);
                $technicians = User::whereIn('id', $validated['technician_ids'])->get();
                foreach ($technicians as $tech) {
                    if (!empty($tech->phone)) {
                        $msg = $this->formatInstallationWaMessage(
                            $customer,
                            $tech,
                            $validated['scheduled_date'],
                            $validated['scheduled_time'],
                            $finalNotes
                        );
                        $res = $waService->sendMessage($tech->phone, $msg);
                        if (!empty($res['success'])) {
                            $waNotifiedCount++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::warning('[assignInstall] Auto WA notification exception: ' . $e->getMessage());
            }
        }

        $successMsg = 'Jadwal pemasangan berhasil ditugaskan kepada teknisi.';
        if ($waNotifiedCount > 0) {
            $successMsg .= " 💬 Notifikasi WhatsApp berhasil terkirim ke {$waNotifiedCount} teknisi.";
        }

        return redirect()->route('customers.installed')
            ->with('success', $successMsg);
    }

    /**
     * Format template pesan WhatsApp notifikasi penugasan jadwal pemasangan ke teknisi
     */
    public function formatInstallationWaMessage(Customer $customer, User $technician, string $date, string $time, ?string $notes = null): string
    {
        $companyName = Setting::get('company_name', config('app.name', 'APM2 Network'));
        $packageName = $customer->package?->name ?? 'Internet Broadband';
        $customerPhone = $customer->phone ?? '-';
        $address = $customer->address ?? '-';
        $areaName = $customer->areaModel?->name ?? $customer->area ?? '-';

        $mapsLink = '';
        if ($customer->latitude && $customer->longitude) {
            $mapsLink = "https://www.google.com/maps?q={$customer->latitude},{$customer->longitude}";
        }

        $survey = $customer->surveys()->latest()->first();
        $odpName = $survey?->odp?->name ?? '-';
        $portOdp = $survey?->port_number ? "Port " . $survey->port_number : '-';
        $cableDist = $survey?->distance_meters ? $survey->distance_meters . " meter" : '-';

        $dateFormatted = date('d-m-Y', strtotime($date));

        $msg = "🛠️ *PENUGASAN INSTALASI PASANG BARU*\n";
        $msg .= "🏢 *{$companyName}*\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "Halo Teknisi *{$technician->name}*,\n";
        $msg .= "Anda telah ditugaskan untuk pekerjaan instalasi internet pelanggan baru:\n\n";

        $msg .= "👤 *Data Pelanggan:*\n";
        $msg .= "• Nama: *{$customer->name}*\n";
        $msg .= "• ID Pelanggan: `{$customer->customer_code}`\n";
        $msg .= "• No. HP / WhatsApp: {$customerPhone}\n";
        $msg .= "• Wilayah / Area: {$areaName}\n";
        $msg .= "• Alamat: {$address}\n";
        if ($mapsLink) {
            $msg .= "• Titik Maps: {$mapsLink}\n";
        }

        $msg .= "\n📦 *Paket & Waktu Pasang:*\n";
        $msg .= "• Paket Internet: *{$packageName}*\n";
        $msg .= "• Tanggal Pasang: *{$dateFormatted}*\n";
        $msg .= "• Jam Pemasangan: *{$time}* WIB\n";

        $msg .= "\n📡 *Data ODP / Jaringan (Hasil Survey):*\n";
        $msg .= "• ODP: *{$odpName}* ({$portOdp})\n";
        $msg .= "• Estimasi Jarak Kabel: {$cableDist}\n";

        if (!empty($notes)) {
            $msg .= "\n🔧 *Alokasi Perangkat & Material:*\n";
            $msg .= "{$notes}\n";
        }

        $msg .= "\n━━━━━━━━━━━━━━━━━━━━\n";
        $msg .= "⚡ _Silakan hubungi pelanggan untuk konfirmasi sebelum meluncur ke lokasi. Setelah pekerjaan instalasi selesai, mohon submit laporan & foto instalasi di sistem. Terima kasih & utamakan K3!_";

        return $msg;
    }

    /**
     * Kirim Pemberitahuan WhatsApp ke Teknisi untuk Jadwal Pasang (API)
     */
    public function notifyTechnicianInstallationWa(Request $request, Customer $customer)
    {
        if (!auth()->user()->can('customers_installed_assign') && !auth()->user()->can('customers_installed_whatsapp') && !auth()->user()->can('customers_btn_whatsapp')) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk mengirim notifikasi WhatsApp teknisi.'], 403);
        }

        $validated = $request->validate([
            'technician_ids' => 'nullable|array',
            'technician_ids.*' => 'exists:users,id',
            'custom_message' => 'nullable|string',
            'scheduled_date' => 'nullable|string',
            'scheduled_time' => 'nullable|string',
        ]);

        $techIds = $validated['technician_ids'] ?? [];
        if (empty($techIds)) {
            $techIds = $customer->technicianSchedules()
                ->where('type', 'installation')
                ->pluck('technician_id')
                ->unique()
                ->toArray();
        }

        if (empty($techIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada teknisi yang ditugaskan pada jadwal instalasi pelanggan ini.'
            ], 422);
        }

        $technicians = User::whereIn('id', $techIds)->get();
        $latestSchedule = $customer->technicianSchedules()->where('type', 'installation')->latest()->first();
        $date = $validated['scheduled_date'] ?? ($latestSchedule?->scheduled_date?->format('Y-m-d') ?? date('Y-m-d'));
        $time = $validated['scheduled_time'] ?? ($latestSchedule?->scheduled_time?->format('H:i') ?? '10:00');
        $notes = $latestSchedule?->notes ?? '';

        $waService = app(\App\Services\WhatsAppGatewayService::class);
        $results = [];
        $sentCount = 0;

        foreach ($technicians as $tech) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $tech->phone ?? '');
            if (str_starts_with($cleanPhone, '0')) {
                $cleanPhone = '62' . substr($cleanPhone, 1);
            }

            $message = !empty($validated['custom_message'])
                ? str_replace('{nama_teknisi}', $tech->name, $validated['custom_message'])
                : $this->formatInstallationWaMessage($customer, $tech, $date, $time, $notes);

            $sendRes = ['success' => false, 'message' => 'Nomor HP teknisi belum diatur di sistem'];
            if (!empty($tech->phone)) {
                $sendRes = $waService->sendMessage($tech->phone, $message);
                if (!empty($sendRes['success'])) {
                    $sentCount++;
                }
            }

            $results[] = [
                'technician_id' => $tech->id,
                'name' => $tech->name,
                'phone' => $tech->phone,
                'clean_phone' => $cleanPhone,
                'wa_url' => $cleanPhone ? "https://wa.me/{$cleanPhone}?text=" . urlencode($message) : null,
                'message' => $message,
                'success' => !empty($sendRes['success']),
                'status_message' => $sendRes['message'] ?? ($sendRes['success'] ? 'Terkirim via Gateway' : 'Gagal'),
            ];
        }

        return response()->json([
            'success' => $sentCount > 0,
            'message' => $sentCount > 0
                ? "Pemberitahuan WhatsApp berhasil dikirim ke {$sentCount} teknisi via Gateway."
                : "Gateway sedang tidak terhubung atau nomor tidak valid. Anda dapat membuka WhatsApp Web/App secara manual.",
            'sent_count' => $sentCount,
            'results' => $results,
        ]);
    }

    /**
     * Simpan Hasil Survey
     */
    public function storeSurvey(Request $request, Customer $customer): RedirectResponse
    {
        $user = auth()->user();
        $isAssigned = $customer->technicianSchedules()->where('type', 'survey')->where('technician_id', $user?->id)->exists();
        if (!$user?->hasRole('admin') && !$user?->can('customers_survey_report') && !$isAssigned) {
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
            'photos' => 'nullable|array',
            'photos.*.label' => 'nullable|string',
            'photos.*.file' => 'nullable|image|max:10240',
        ], [
            'odp_id.required_if' => 'ODP harus dipilih jika status Feasible.',
            'port_number.required_if' => 'Port ODP harus dipilih jika status Feasible.',
            'distance_meters.required_if' => 'Jarak kabel harus diisi jika status Feasible.',
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
        
        // Update status jadwal teknisi survey ke 'done' untuk semua teknisi yang ditugaskan
        $customer->technicianSchedules()
            ->where('type', 'survey')
            ->where('status', 'scheduled')
            ->update(['status' => 'done']);

        // Catatan: Seluruh insentif (Fee Survey, Fee Booking, Fee Pasang) hanya dibentuk saat pelanggan selesai AKTIVASI di CustomerController::activate

        if ($validated['feasibility'] === 'feasible') {
            \App\Models\AuditLog::createLog('Laporan Survey', $customer, 'survey', 'survey', 'Mengisi laporan survey (feasible)');
            // Biarkan status tetap survey, agar tombol 'Ready Install' muncul
            return redirect()->route('customers.survey')
                ->with('success', 'Hasil survey berhasil disimpan. Pelanggan kini siap untuk instalasi (Ready Install).');
        } else {
            $customer->update([
                'status' => 'terminated',
                'cancel_stage' => 'survey',
                'cancel_reason' => 'Hasil Survey: Tidak Layak (' . ($validated['notes'] ?? 'Jaringan/port tidak mencukupi') . ')',
                'cancel_date' => now()->toDateString(),
            ]);
            \App\Models\AuditLog::createLog('Laporan Survey', $customer, 'survey', 'terminated', 'Mengisi laporan survey (not feasible)');
            return redirect()->route('customers.survey')
                ->with('success', 'Hasil survey (not feasible) berhasil disimpan. Pelanggan dipindahkan ke menu Dibatalkan.');
        }
    }

    public function updateSurvey(Request $request, Customer $customer): RedirectResponse
    {
        $user = auth()->user();
        $isAssigned = $customer->technicianSchedules()->where('type', 'survey')->where('technician_id', $user?->id)->exists()
            || ($customer->surveys()->first()?->surveyor_id === $user?->id);
        if (!$user?->hasRole('admin') && !$user?->can('customers_survey_report') && !$isAssigned) {
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
            'photos.*.file' => 'nullable|image|max:10240',
        ]);

        if (!empty($validated['odp_id'])) {
            $odp = Odp::findOrFail($validated['odp_id']);
            if ($odp->area_id !== $customer->area_id) {
                return back()->withErrors(['odp_id' => 'ODP tidak sesuai dengan Area/Wilayah pelanggan.']);
            }
            
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
            // Jika not feasible, lepaskan port yang mungkin tadinya di-reservasi
            if ($survey->odp_id && $survey->port_number) {
                \App\Models\OdpPort::where('odp_id', $survey->odp_id)
                    ->where('port_number', $survey->port_number)
                    ->where('status', 'reserved')
                    ->update(['status' => 'available']);
            }
            \App\Models\AuditLog::createLog('Laporan Survey', $customer, 'survey', 'terminated', 'Mengubah laporan survey (not feasible)');
        }

        return redirect()->route('customers.survey')->with('success', 'Hasil survey berhasil diubah.');
    }

    /**
     * Arsipkan hasil survey dan buka kembali tahap pengisian laporan.
     */
    public function rollbackSurvey(Customer $customer): RedirectResponse
    {
        if (!auth()->user()->can('customers_survey_rollback') && !auth()->user()->can('customers_survey_edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengulang laporan survey.');
        }

        if ($customer->status !== 'survey') {
            return back()->with('error', 'Rollback hanya dapat dilakukan pada pelanggan yang masih berada di tahap Survey.');
        }

        $surveys = $customer->surveys()->get();
        if ($surveys->isEmpty()) {
            return back()->with('error', 'Tidak ada laporan survey yang dapat di-roll back.');
        }

        $oldStatus = $customer->status;
        DB::transaction(function () use ($customer, $surveys, $oldStatus) {
            foreach ($surveys as $survey) {
                // Port hanya dilepas bila masih berupa reservasi survey.
                if ($survey->odp_id && $survey->port_number) {
                    \App\Models\OdpPort::where('odp_id', $survey->odp_id)
                        ->where('port_number', $survey->port_number)
                        ->where('status', 'reserved')
                        ->update([
                            'status' => 'available',
                            'description' => null,
                        ]);
                }

                $survey->update([
                    'rolled_back_at' => now(),
                    'rolled_back_by' => auth()->id(),
                ]);
            }

            // Pertahankan teknisi dan jadwal yang sama, tetapi buka kembali tahap pelaporannya.
            $customer->technicianSchedules()
                ->where('type', 'survey')
                ->where('status', 'done')
                ->update(['status' => 'scheduled']);

            $customer->update(['status' => 'survey']);

            \App\Models\AuditLog::createLog(
                'Rollback Survey',
                $customer,
                $oldStatus,
                'survey',
                'Mengarsipkan hasil laporan survey agar teknisi dapat mengisi laporan ulang.'
            );
        });

        return redirect()->route('customers.survey')
            ->with('success', 'Laporan survey lama diarsipkan. Jadwal dan petugas tetap dipertahankan untuk mengisi laporan baru.');
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
        $user = auth()->user();
        $canCancel = $user && (
            $user->hasRole('admin') ||
            $user->can('customers_delete') ||
            $user->can('customers_booking_delete') ||
            $user->can('customers_survey_cancel') ||
            $user->can('customers_installed_cancel') ||
            $user->can('menu_customers_booking') ||
            $user->can('menu_customers_survey') ||
            $user->can('menu_customers_installed') ||
            $user->can('menu_customers_all')
        );

        if (!$canCancel) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk membatalkan pendaftaran/pemasangan pelanggan.');
        }

        $request->validate([
            'cancel_reason' => 'required|string|max:1000',
            'cancel_date' => 'required|date',
            'cancel_stage' => 'nullable|string|max:50',
        ]);

        $oldStatus = $customer->status;

        // Tentukan tahap pembatalan
        $stage = $request->cancel_stage;
        if (!$stage) {
            if ($oldStatus === 'survey') {
                $stage = 'survey';
            } elseif ($oldStatus === 'installing') {
                $stage = 'installing';
            } elseif ($oldStatus === 'audit' || $oldStatus === 'menunggu_aktivasi') {
                $stage = 'activation';
            } else {
                $stage = 'booking';
            }
        }

        $stageLabels = [
            'booking' => 'Batal Booking',
            'survey' => 'Batal Survey',
            'installing' => 'Batal Pasang',
            'activation' => 'Batal Aktivasi',
        ];

        $prefix = $stageLabels[$stage] ?? 'Batal';
        $reason = trim($request->cancel_reason);
        if (!str_starts_with(strtolower($reason), strtolower($prefix))) {
            $reason = "{$prefix}: {$reason}";
        }

        $customer->update([
            'status' => 'canceled',
            'cancel_stage' => $stage,
            'cancel_reason' => $reason,
            'cancel_date' => $request->cancel_date,
        ]);

        // 1. Kembalikan seluruh material pengeluaran instalasi (OUT) ke stok gudang & bersihkan transaksi
        try {
            \App\Services\MaterialHandoverService::restoreDispatchedMaterials(
                'instalasi',
                $customer->id,
                $customer->area_id,
                $customer->customer_code ?? ('CUST-' . $customer->id)
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[CustomerController::cancel] Gagal restore material handover: ' . $e->getMessage());
        }

        // 2. Kembalikan seluruh material terpasang (CustomerInstalledMaterial) ke stok gudang
        try {
            $installedMaterials = \App\Models\CustomerInstalledMaterial::where('customer_id', $customer->id)->get();
            foreach ($installedMaterials as $installed) {
                if ($installed->material_id && (float) $installed->quantity > 0) {
                    \App\Services\CustomerMaterialService::returnMaterialToWarehouse(
                        (int) $installed->material_id,
                        (float) $installed->quantity,
                        $customer->area_id,
                        $installed->unit
                    );
                }
                try { $installed->dismantles()->delete(); } catch (\Throwable $de) {}
                $installed->delete();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[CustomerController::cancel] Gagal restore installed materials: ' . $e->getMessage());
        }

        // 3. Lepaskan port ODP yang di-reservasi oleh survey jika ada
        foreach ($customer->surveys as $survey) {
            if ($survey->odp_id && $survey->port_number) {
                \App\Models\OdpPort::where('odp_id', $survey->odp_id)
                    ->where('port_number', $survey->port_number)
                    ->where('status', 'reserved')
                    ->update(['status' => 'available']);
            }
        }

        // 4. Lepaskan seluruh ONT terpasang & kembalikan ke status Belum Set (Gudang)
        $customerOnts = \App\Models\Ont::where('customer_id', $customer->id)->get();
        foreach ($customerOnts as $cOnt) {
            if ($cOnt->odp_id && $cOnt->port_number) {
                \App\Models\OdpPort::where('odp_id', $cOnt->odp_id)
                    ->where('port_number', $cOnt->port_number)
                    ->update(['status' => 'available']);
            }
            $cOnt->update([
                'customer_id' => null,
                'odp_id' => null,
                'odp_port_id' => null,
                'port_number' => null,
                'status' => 'Belum Set/Baru Input',
                'rx_power' => null,
                'tx_power' => null,
                'description' => 'Kembali ke Gudang (Batal Instalasi Pelanggan ' . $customer->name . ' [' . ($customer->customer_code ?? $customer->id) . '])',
            ]);
        }

        // 5. Batalkan dan bersihkan jadwal teknisi instalasi
        $customer->technicianSchedules()
            ->where('type', 'installation')
            ->delete();

        $customer->technicianSchedules()
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->update(['status' => 'canceled']);

        \App\Models\AuditLog::createLog('Pelanggan Dibatalkan', $customer, $oldStatus, 'canceled', "Pendaftaran dibatalkan: {$reason}");

        return redirect()->back()->with('success', "Pendaftaran pelanggan berhasil dibatalkan ({$prefix}).");
    }

    public function restore(Customer $customer): RedirectResponse
    {
        $oldStatus = $customer->status;
        $customer->update([
            'status' => 'booking',
            'cancel_stage' => null,
            'cancel_reason' => null,
            'cancel_date' => null,
        ]);

        \App\Models\AuditLog::createLog('Restore Pelanggan', $customer, $oldStatus, 'booking', 'Mengembalikan pelanggan yang dibatalkan ke antrean booking');

        return redirect()->back()->with('success', "Pelanggan {$customer->name} berhasil dikembalikan ke status Booking.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:customers,id'
        ]);

        $customers = Customer::whereIn('id', $request->ids)->get();
        foreach ($customers as $customer) {
            $oldStatus = $customer->status;
            $customer->update([
                'status' => 'booking',
                'cancel_stage' => null,
                'cancel_reason' => null,
                'cancel_date' => null,
            ]);
            \App\Models\AuditLog::createLog('Restore Pelanggan', $customer, $oldStatus, 'booking', 'Mengembalikan pelanggan yang dibatalkan ke antrean booking');
        }

        return redirect()->back()->with('success', count($request->ids) . ' data pelanggan berhasil dikembalikan ke status Booking.');
    }

    /**
     * Hapus massal data pelanggan beserta seluruh relasi & data terhubung
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $canDelete = $user && (
            $user->hasRole('admin') ||
            $user->can('customers_delete') ||
            $user->can('customers_booking_delete') ||
            $user->can('customers_survey_delete') ||
            $user->can('customers_installed_delete') ||
            $user->can('menu_customers_booking') ||
            $user->can('menu_customers_survey') ||
            $user->can('menu_customers_installed') ||
            $user->can('menu_customers_all')
        );

        if (!$canDelete) {
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
                \App\Models\AuditLog::createLog(
                    'Hapus Pelanggan (Massal)',
                    $customer,
                    $customer->status,
                    'deleted',
                    "Pelanggan {$customer->name} ({$customer->customer_code}) dihapus via bulk delete."
                );

                // Customer::deleting event otomatis memicu purgeConnectedData()
                $customer->delete();
                $count++;
            }
        });

        return redirect()->back()->with('success', "$count data pelanggan beserta seluruh data terhubung berhasil dihapus secara permanen.");
    }

    /**
     * Isolir Pelanggan (suspend)
     */
    public function suspend(Request $request, Customer $customer, RadiusService $radius): RedirectResponse
    {
        if ($customer->status === 'suspended') {
            return redirect()->back()->with('info', 'Pelanggan sudah dalam status isolir.');
        }

        $customer->update([
            'status' => 'suspended',
            'suspend_start' => now()->toDateString(),
            'suspend_end' => null,
        ]);

        try {
            $radius->syncCustomer($customer);
            $accounts = $radius->customerAccounts($customer);
            foreach (array_keys($accounts) as $username) {
                $radius->disconnect($username);
            }
        } catch (\Throwable $e) {
            \Log::warning('[RADIUS] Suspend sync error: ' . $e->getMessage());
        }

        \App\Models\AuditLog::createLog('Isolir Layanan', $customer, 'active', 'suspended', 'Mengisolir layanan internet pelanggan');

        return redirect()->back()->with('success', "Isolir pelanggan {$customer->name} berhasil diaktifkan.");
    }

    /**
     * Buka Isolir Pelanggan (unsuspend)
     */
    public function unsuspend(Request $request, Customer $customer, RadiusService $radius): RedirectResponse
    {
        if ($customer->status !== 'suspended') {
            return redirect()->back()->with('info', 'Pelanggan tidak dalam status isolir.');
        }

        $customer->update([
            'status' => 'active',
            'suspend_start' => null,
            'suspend_end' => null,
        ]);

        try {
            $radius->syncCustomer($customer);
        } catch (\Throwable $e) {
            \Log::warning('[RADIUS] Unsuspend sync error: ' . $e->getMessage());
        }

        \App\Models\AuditLog::createLog('Buka Isolir', $customer, 'suspended', 'active', 'Membuka isolir pelanggan');

        return redirect()->back()->with('success', "Isolir pelanggan {$customer->name} berhasil dibuka.");
    }

    /**
     * Helper untuk filter query Customer berdasarkan Area (id, nama, variasi hyphen/space)
     */
    private function applyAreaFilter($query, $areaVal)
    {
        if (!$areaVal) return $query;

        $areaDict = DataScopeService::getAreaDictionary(); // [id => name], cached in memory
        $matchedIds = [];
        $areaName = null;

        if (is_numeric($areaVal)) {
            $id = (int) $areaVal;
            $matchedIds[] = $id;
            $areaName = $areaDict[$id] ?? null;
        } else {
            $areaName = trim((string) $areaVal);
            $normTarget = mb_strtolower(str_replace('-', ' ', $areaName));
            foreach ($areaDict as $id => $name) {
                $normName = mb_strtolower(str_replace('-', ' ', trim((string) $name)));
                if ($normName === $normTarget || mb_strtolower(trim((string) $name)) === mb_strtolower($areaName)) {
                    $matchedIds[] = (int) $id;
                    $areaName = $name;
                }
            }
        }

        $stringVariants = array_values(array_unique(array_filter([
            !is_numeric($areaVal) ? trim((string) $areaVal) : null,
            $areaName,
            !is_numeric($areaVal) ? str_replace(' ', '-', trim((string) $areaVal)) : null,
            !is_numeric($areaVal) ? str_replace('-', ' ', trim((string) $areaVal)) : null,
            $areaName ? str_replace(' ', '-', $areaName) : null,
            $areaName ? str_replace('-', ' ', $areaName) : null,
        ])));

        return $query->where(function ($sub) use ($matchedIds, $stringVariants) {
            if (!empty($matchedIds)) {
                $sub->whereIn('area_id', $matchedIds);
                if (!empty($stringVariants)) {
                    $sub->orWhereIn('area', $stringVariants);
                }
            } elseif (!empty($stringVariants)) {
                $sub->whereIn('area', $stringVariants);
            }
        });
    }
}

