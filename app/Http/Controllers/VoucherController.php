<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\VoucherProfile;
use App\Models\Customer;
use App\Models\Reseller;
use App\Models\Radius\RadAcct;
use App\Services\RadiusService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    public function free(Request $request)
    {
        $user = auth()->user();
        if ($user && !$user->isAdmin() && !$user->can('menu_vouchers_free') && !$user->can('menu_vouchers')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka Menu Voucher Gratis.');
        }

        // Jalankan pengecekan voucher expired terlebih dahulu
        self::checkAndExpireVouchers();

        // Online Usernames dari RADIUS
        $onlineUsernames = (function() {
            try {
                return RadAcct::online()->pluck('username')->toArray();
            } catch (\Exception $e) {
                return [];
            }
        })();

        // 1. Query Data Voucher Gratis (Hasil Generate & Khusus)
        $voucherQuery = Voucher::with(['profile', 'customer.areaModel', 'reseller.customer'])
            ->where(function($q) {
                $q->where('is_free', true)
                  ->orWhere('payment_method', 'free')
                  ->orWhereHas('profile', function($pq) {
                      $pq->where('is_customer_free', true)->orWhere('price', 0);
                  });
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $voucherQuery->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%")
                          ->orWhere('customer_code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('reseller.customer', function($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'online') {
                $voucherQuery->whereIn('username', $onlineUsernames);
            } elseif ($request->status === 'offline') {
                $voucherQuery->where(function($q) use ($onlineUsernames) {
                    $q->whereNotIn('username', $onlineUsernames)
                      ->orWhereNull('username');
                });
            } else {
                $voucherQuery->where('status', $request->status);
            }
        }

        if ($request->filled('profile_id')) {
            $voucherQuery->where('voucher_profile_id', $request->profile_id);
        }

        $vouchers = $voucherQuery->latest('id')->paginate(20, ['*'], 'vouchers_page')->withQueryString();

        // 2. Query Data Pelanggan Hotspot Gratis (Dari Aktivasi ONT)
        $customerQuery = Customer::with(['ont', 'areaModel', 'package'])
            ->whereHas('ont', function($q) {
                $q->where('free_hotspot', true);
            });
            
        if ($request->filled('search_customer')) {
            $searchCust = $request->search_customer;
            $customerQuery->where(function($q) use ($searchCust) {
                $q->where('name', 'like', "%{$searchCust}%")
                  ->orWhere('customer_code', 'like', "%{$searchCust}%")
                  ->orWhereHas('ont', function($subQ) use ($searchCust) {
                      $subQ->where('hotspot_user', 'like', "%{$searchCust}%");
                  });
            });
        }

        if ($request->filled('connection_status')) {
            if ($request->connection_status === 'online') {
                $customerQuery->whereHas('ont', function($q) use ($onlineUsernames) {
                    $q->where('free_hotspot', true)->whereIn('hotspot_user', $onlineUsernames);
                });
            } elseif ($request->connection_status === 'offline') {
                $customerQuery->whereHas('ont', function($q) use ($onlineUsernames) {
                    $q->where('free_hotspot', true)->where(function($sq) use ($onlineUsernames) {
                        $sq->whereNotIn('hotspot_user', $onlineUsernames)->orWhereNull('hotspot_user');
                    });
                });
            }
        }
        
        $customers = $customerQuery->latest()->paginate(15, ['*'], 'customers_page')->withQueryString();

        // 3. Statistik Voucher Gratis
        $statsBase = Voucher::query()
            ->where(function($q) {
                $q->where('is_free', true)
                  ->orWhere('payment_method', 'free')
                  ->orWhereHas('profile', function($pq) {
                      $pq->where('is_customer_free', true)->orWhere('price', 0);
                  });
            });

        $totalFree = (clone $statsBase)->count();
        $availableFree = (clone $statsBase)->where('status', 'available')->count();
        $usedFree = (clone $statsBase)->where('status', 'used')->count();
        $expiredFree = (clone $statsBase)->where('status', 'expired')->count();
        $allFreeUsernames = (clone $statsBase)->pluck('username')->filter()->toArray();
        $onlineFree = count(array_intersect($onlineUsernames, $allFreeUsernames));
        $offlineFree = max(0, $totalFree - $onlineFree);

        // Statistik Pelanggan ONT
        $baseCustQuery = Customer::whereHas('ont', fn($q) => $q->where('free_hotspot', true));
        $ontTotal = (clone $baseCustQuery)->count();
        $ontOnline = (clone $baseCustQuery)->whereHas('ont', fn($q) => $q->whereIn('hotspot_user', $onlineUsernames))->count();
        $ontOffline = max(0, $ontTotal - $ontOnline);

        // 4. Profil Voucher yang tersedia
        $freeProfiles = VoucherProfile::where(function($q) {
            $q->where('is_customer_free', true)->orWhere('price', 0);
        })->get();

        $allProfiles = VoucherProfile::all();
        $freeProfile = $freeProfiles->first() ?? $allProfiles->first();

        $resellers = Reseller::with('customer')->get();
        $customerList = Customer::select('id', 'name', 'customer_code')->where('status', 'active')->limit(150)->get();

        return Inertia::render('Vouchers/Free/Index', [
            'vouchers' => $vouchers,
            'customers' => $customers,
            'freeProfile' => $freeProfile,
            'freeProfiles' => $freeProfiles,
            'allProfiles' => $allProfiles,
            'resellers' => $resellers,
            'customerList' => $customerList,
            'onlineUsernames' => $onlineUsernames,
            'stats' => [
                'total' => $totalFree,
                'available' => $availableFree,
                'used' => $usedFree,
                'expired' => $expiredFree,
                'online' => $onlineFree,
                'offline' => $offlineFree,
                'customer_count' => $ontTotal,
                'ont_online' => $ontOnline,
                'ont_offline' => $ontOffline,
            ],
            'filters' => $request->only(['search', 'status', 'profile_id', 'tab', 'search_customer', 'connection_status'])
        ]);
    }

    /**
     * Otomatis deteksi voucher yang waktu durasi / masa aktifnya sudah habis,
     * tandai sebagai expired, dan putuskan dari RADIUS.
     */
    public static function checkAndExpireVouchers(): int
    {
        try {
            $vouchers = Voucher::with('profile')
                ->where('status', '!=', 'expired')
                ->whereHas('profile', function ($q) {
                    $q->whereNotNull('duration')
                      ->where('duration', '!=', '')
                      ->where('duration', '!=', 'Mengikuti Pelanggan');
                })
                ->get();

            if ($vouchers->isEmpty()) {
                return 0;
            }

            $usernames = $vouchers->pluck('username')->filter()->unique()->toArray();
            if (empty($usernames)) {
                return 0;
            }

            $accts = \App\Models\Radius\RadAcct::whereIn('username', $usernames)->get()->groupBy('username');
            $expiredCount = 0;
            $radius = null;

            foreach ($vouchers as $voucher) {
                $userAccts = $accts->get($voucher->username);
                if (!$userAccts || $userAccts->isEmpty()) {
                    continue;
                }

                $durationLimit = RadiusService::parseDuration($voucher->profile->duration);
                if (!$durationLimit) {
                    continue;
                }

                $totalTime = 0;
                $firstLogin = null;
                $lastLogout = null;

                foreach ($userAccts as $acct) {
                    if (!$firstLogin || $acct->acctstarttime < $firstLogin) {
                        $firstLogin = $acct->acctstarttime;
                    }
                    if (!$lastLogout || $acct->acctstoptime > $lastLogout) {
                        $lastLogout = $acct->acctstoptime;
                    }

                    $totalTime += $acct->liveSessionTime();
                }

                $isExpired = false;
                $expiredAt = null;

                if ($totalTime >= $durationLimit) {
                    $isExpired = true;
                    $expiredAt = $lastLogout ?? now();
                } elseif ($firstLogin) {
                    $calcExpire = \Carbon\Carbon::parse($firstLogin)->addSeconds($durationLimit);
                    if (now()->greaterThanOrEqualTo($calcExpire)) {
                        $isExpired = true;
                        $expiredAt = $calcExpire;
                    }
                }

                if ($isExpired) {
                    $voucher->status = 'expired';
                    $voucher->used_at = $expiredAt ?? now();
                    $voucher->save();

                    if (!$radius) {
                        $radius = app(RadiusService::class);
                    }
                    $radius->disconnect($voucher->username);
                    $radius->removeUser($voucher->username);

                    $expiredCount++;
                } elseif ($voucher->status === 'available' && ($firstLogin || $totalTime > 0)) {
                    // Jika voucher sudah mulai digunakan tetapi belum habis, set status used
                    $voucher->status = 'used';
                    $voucher->used_at = $firstLogin;
                    $voucher->save();
                }
            }

            return $expiredCount;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Failed checking expired vouchers: " . $e->getMessage());
            return 0;
        }
    }

    public function index(Request $request)
    {
        // Jalankan pengecekan voucher expired terlebih dahulu
        self::checkAndExpireVouchers();

        // Online Usernames dari RADIUS
        $onlineUsernames = (function() {
            try {
                return \App\Models\Radius\RadAcct::online()->pluck('username')->toArray();
            } catch (\Exception $e) {
                return [];
            }
        })();

        $query = Voucher::with(['profile', 'reseller.customer.areaModel', 'customer.areaModel']);

        if ($request->filled('profile_id')) {
            $query->where('vouchers.voucher_profile_id', $request->profile_id);
        }

        if ($request->filled('status')) {
            if ($request->status === 'online') {
                $query->whereIn('vouchers.username', $onlineUsernames);
            } elseif ($request->status === 'offline') {
                $query->where(function($q) use ($onlineUsernames) {
                    $q->whereNotIn('vouchers.username', $onlineUsernames)
                      ->orWhereNull('vouchers.username');
                });
            } else {
                $query->where('vouchers.status', $request->status);
            }
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('vouchers.code', 'like', "%{$search}%")
                  ->orWhere('vouchers.username', 'like', "%{$search}%")
                  ->orWhere('vouchers.buyer_name', 'like', "%{$search}%")
                  ->orWhere('vouchers.buyer_phone', 'like', "%{$search}%")
                  ->orWhereHas('reseller.customer', function($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter Pemilik / Reseller
        if ($request->filled('reseller_id')) {
            if ($request->reseller_id === 'system') {
                $query->whereNull('vouchers.reseller_id');
            } else {
                $query->where('vouchers.reseller_id', $request->reseller_id);
            }
        }

        // Filter Wilayah / Area
        if ($request->filled('area_id')) {
            $areaId = $request->area_id;
            $selectedArea = \App\Models\Area::find($areaId);
            $areaName = $selectedArea ? $selectedArea->name : null;

            $query->where(function ($q) use ($areaId, $areaName) {
                $q->whereHas('reseller.customer', function ($sub) use ($areaId, $areaName) {
                    $sub->where('area_id', $areaId);
                    if ($areaName) {
                        $sub->orWhere('area', $areaName);
                    }
                })->orWhereHas('customer', function ($sub) use ($areaId, $areaName) {
                    $sub->where('area_id', $areaId);
                    if ($areaName) {
                        $sub->orWhere('area', $areaName);
                    }
                });
            });
        }
        
        $this->applyResellerFilter($query);

        $vouchers = $query->latest('vouchers.id')->paginate(20)->withQueryString();
        $user = auth()->user();
        $isAdminLike = $user && ($user->isAdmin() || in_array(strtolower($user->role ?? ''), ['admin', 'super admin', 'superadmin', 'demo']));
        $isResellerPortal = $user && (
            strtolower($user->role ?? '') === 'reseller' 
            || strtolower($user->role ?? '') === 'biller' 
            || session('login_portal') === 'reseller'
            || ($user->reseller && !$isAdminLike)
        );

        if ($isAdminLike && session('login_portal') !== 'reseller') {
            $profiles = VoucherProfile::all();
        } else {
            $profiles = $user ? $user->voucherProfiles()->where('voucher_profiles.is_customer_free', false)->select('voucher_profiles.*')->get() : collect();
            if ($profiles->isEmpty()) {
                $profiles = VoucherProfile::where('is_customer_free', false)->get();
            }
        }
        
        $currentReseller = null;
        if (($isResellerPortal || $user?->reseller) && $user) {
            $currentReseller = $this->getOrCreateReseller($user);
            if ($currentReseller) {
                $currentReseller->load('customer');
            }
        }

        $resellers = \App\Models\Reseller::with('customer.areaModel')->get();
        $areas = \App\Models\Area::orderBy('name')->get(['id', 'name']);

        // Query Dasar Statistik (mengikuti batasan user / reseller / area yang dipilih)
        $statsBase = Voucher::query();
        $this->applyResellerFilter($statsBase);

        if ($request->filled('reseller_id')) {
            if ($request->reseller_id === 'system') {
                $statsBase->whereNull('vouchers.reseller_id');
            } else {
                $statsBase->where('vouchers.reseller_id', $request->reseller_id);
            }
        }

        if ($request->filled('area_id')) {
            $areaId = $request->area_id;
            $selectedArea = \App\Models\Area::find($areaId);
            $areaName = $selectedArea ? $selectedArea->name : null;

            $statsBase->where(function ($q) use ($areaId, $areaName) {
                $q->whereHas('reseller.customer', function ($sub) use ($areaId, $areaName) {
                    $sub->where('area_id', $areaId);
                    if ($areaName) {
                        $sub->orWhere('area', $areaName);
                    }
                })->orWhereHas('customer', function ($sub) use ($areaId, $areaName) {
                    $sub->where('area_id', $areaId);
                    if ($areaName) {
                        $sub->orWhere('area', $areaName);
                    }
                });
            });
        }

        $totalCount = (clone $statsBase)->count();
        $availableCount = (clone $statsBase)->where('vouchers.status', 'available')->count();
        $usedCount = (clone $statsBase)->where('vouchers.status', 'used')->count();
        $expiredCount = (clone $statsBase)->where('vouchers.status', 'expired')->count();
        $onlineCount = (clone $statsBase)->whereIn('vouchers.username', $onlineUsernames)->count();

        // Nominal omset dari voucher terpakai / terjual (berbayar)
        $totalNominalUsed = (float) (clone $statsBase)->where('vouchers.status', 'used')
            ->where('vouchers.is_free', false)
            ->join('voucher_profiles', 'vouchers.voucher_profile_id', '=', 'voucher_profiles.id')
            ->sum('voucher_profiles.price');

        // Nominal potensi dari voucher yang tersedia (berbayar)
        $totalNominalAvailable = (float) (clone $statsBase)->where('vouchers.status', 'available')
            ->where('vouchers.is_free', false)
            ->join('voucher_profiles', 'vouchers.voucher_profile_id', '=', 'voucher_profiles.id')
            ->sum('voucher_profiles.price');

        $stats = [
            'total' => $totalCount,
            'available' => $availableCount,
            'used' => $usedCount,
            'expired' => $expiredCount,
            'online' => $onlineCount,
            'nominal_used' => $totalNominalUsed,
            'nominal_available' => $totalNominalAvailable,
        ];

        return Inertia::render('Vouchers/Index', [
            'vouchers' => $vouchers,
            'profiles' => $profiles,
            'resellers' => $resellers,
            'currentReseller' => $currentReseller,
            'isResellerPortal' => $isResellerPortal,
            'areas' => $areas,
            'stats' => $stats,
            'filters' => [
                'search' => $request->input('search', ''),
                'profile_id' => $request->input('profile_id', ''),
                'status' => $request->input('status', ''),
                'reseller_id' => $request->input('reseller_id', ''),
                'area_id' => $request->input('area_id', ''),
            ],
            'onlineUsernames' => $onlineUsernames,
            'usageStats' => (function() use ($vouchers) {
                try {
                    $usernames = $vouchers->pluck('username')->filter()->toArray();
                    if (empty($usernames)) return (object)[];
                    
                    $stats = [];
                    $records = \App\Models\Radius\RadAcct::whereIn('username', $usernames)->get();
                    
                    foreach ($records as $record) {
                        $user = $record->username;
                        if (!isset($stats[$user])) {
                            $stats[$user] = (object)[
                                'first_login' => null,
                                'total_time' => 0,
                            ];
                        }
                        
                        // Cari first login
                        if (!$stats[$user]->first_login || $record->acctstarttime < $stats[$user]->first_login) {
                            $stats[$user]->first_login = $record->acctstarttime;
                        }
                        
                        // Hitung total time (tambahkan durasi sesi aktif jika belum ditutup)
                        if (is_null($record->acctstoptime) && $record->acctstarttime) {
                            $stats[$user]->total_time += now()->diffInSeconds($record->acctstarttime);
                        } else {
                            $stats[$user]->total_time += $record->acctsessiontime;
                        }
                    }
                    
                    // Format dates for JS
                    foreach ($stats as $user => $s) {
                        $s->first_login = $s->first_login ? $s->first_login->toIso8601String() : null;
                    }
                    
                    return collect($stats);
                } catch (\Exception $e) {
                    return (object)[];
                }
            })(),
        ]);
    }

    public function online(Request $request)
    {
        // 0. Otomatis cek dan sinkronkan status voucher yang durasinya habis menjadi expired
        self::checkAndExpireVouchers();

        $user = auth()->user();
        $isResellerPortal = $user && (strtolower($user->role ?? '') === 'reseller' || session('login_portal') === 'reseller');

        $tab = $request->input('tab', 'online');
        $search = $request->input('search');
        $profileId = $request->input('profile_id');
        $resellerId = $request->input('reseller_id');
        $areaId = $request->input('area_id');

        // Master dropdown filters
        if ($isResellerPortal && $user) {
            $profiles = $user->voucherProfiles()->where('voucher_profiles.is_customer_free', false)
                ->select('voucher_profiles.id', 'voucher_profiles.name', 'voucher_profiles.duration', 'voucher_profiles.price')
                ->get();
            if ($profiles->isEmpty()) {
                $profiles = VoucherProfile::where('is_customer_free', false)->orderBy('name')->get(['id', 'name', 'duration', 'price']);
            }
        } else {
            $profiles = VoucherProfile::orderBy('name')->get(['id', 'name', 'duration', 'price']);
        }
        $resellers = \App\Models\Reseller::with('customer.areaModel')->get();
        $areas = \App\Models\Area::orderBy('name')->get(['id', 'name']);

        // 1. Ambil Sesi Online Aktif dari RADIUS
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

        // 2. Ambil data Voucher yang valid (mengikuti filter reseller jika login sebagai reseller)
        $vouchersQuery = Voucher::with(['profile', 'reseller.customer.areaModel', 'customer.areaModel']);
        $this->applyResellerFilter($vouchersQuery);
        $allVouchers = $vouchersQuery->get();

        $vouchersByUsername = $allVouchers->keyBy('username');
        $vouchersByCode = $allVouchers->keyBy('code');

        // Helper filter matcher for a voucher/session item
        $matchesFilter = function($voucher, $session, $search, $profileId, $resellerId, $areaId) {
            // Filter Profile
            if (!empty($profileId)) {
                if (!$voucher || (int)$voucher->voucher_profile_id !== (int)$profileId) {
                    return false;
                }
            }

            // Filter Reseller
            if (!empty($resellerId)) {
                if ($resellerId === 'system') {
                    if ($voucher && !is_null($voucher->reseller_id)) {
                        return false;
                    }
                } else {
                    if (!$voucher || (int)$voucher->reseller_id !== (int)$resellerId) {
                        return false;
                    }
                }
            }

            // Filter Area
            if (!empty($areaId)) {
                $voucherAreaId = $voucher?->reseller?->customer?->area_id ?? $voucher?->customer?->area_id;
                $voucherAreaName = $voucher?->reseller?->customer?->areaModel?->name ?? ($voucher?->reseller?->customer?->area ?? ($voucher?->customer?->areaModel?->name ?? ($voucher?->customer?->area ?? null)));
                $targetArea = \App\Models\Area::find($areaId);
                $targetAreaName = $targetArea ? $targetArea->name : null;

                $match = false;
                if ($voucherAreaId && (int)$voucherAreaId === (int)$areaId) {
                    $match = true;
                } elseif ($targetAreaName && $voucherAreaName && strcasecmp($voucherAreaName, $targetAreaName) === 0) {
                    $match = true;
                }
                if (!$match) return false;
            }

            // Filter Search
            if (!empty($search)) {
                $q = strtolower(trim($search));
                $code = strtolower($voucher?->code ?? '');
                $username = strtolower($voucher?->username ?? ($session?->username ?? ''));
                $profileName = strtolower($voucher?->profile?->name ?? '');
                $resellerName = strtolower($voucher?->reseller?->customer?->name ?? 'kantor system');
                $areaName = strtolower($voucher?->reseller?->customer?->areaModel?->name ?? ($voucher?->reseller?->customer?->area ?? ''));
                $ip = strtolower($session?->framedipaddress ?? '');
                $mac = strtolower($session?->callingstationid ?? '');

                $buyerName = strtolower($voucher?->buyer_name ?? '');
                $buyerPhone = strtolower($voucher?->buyer_phone ?? '');

                if (
                    strpos($code, $q) === false &&
                    strpos($username, $q) === false &&
                    strpos($buyerName, $q) === false &&
                    strpos($buyerPhone, $q) === false &&
                    strpos($profileName, $q) === false &&
                    strpos($resellerName, $q) === false &&
                    strpos($areaName, $q) === false &&
                    strpos($ip, $q) === false &&
                    strpos($mac, $q) === false
                ) {
                    return false;
                }
            }

            return true;
        };

        // 3. Build Online List: Hanya sesi online yang benar-benar merupakan voucher aktif
        $onlineList = collect();
        foreach ($onlineSessions as $username => $s) {
            $v = $vouchersByUsername->get($username) ?? $vouchersByCode->get($username);
            if (!$v) {
                // Bukan voucher hotspot terdaftar (misal PPPoE test / user luar)
                continue;
            }
            if ($v->status === 'expired' || !$v->is_active) {
                continue;
            }

            if (!$matchesFilter($v, $s, $search, $profileId, $resellerId, $areaId)) {
                continue;
            }

            $resellerCustomer = $v->reseller?->customer;
            $areaName = $resellerCustomer?->areaModel?->name ?? ($resellerCustomer?->area ?: ($v->customer?->areaModel?->name ?? ($v->customer?->area ?: '-')));

            $onlineList->push([
                'id' => $v->id,
                'voucher_id' => $v->id,
                'code' => $v->code,
                'username' => $s->username,
                'profile' => $v->profile?->name ?? 'MikroTik Hotspot',
                'profile_duration' => $v->profile?->duration,
                'reseller' => $resellerCustomer?->name ?? 'Kantor / System',
                'area' => $areaName,
                'ip_address' => $s->framedipaddress ?? '-',
                'mac_address' => $s->callingstationid ?? '-',
                'nas_ip' => $s->nasipaddress ?? '-',
                'uptime' => $s->liveSessionTime(),
                'download' => (int) ($s->acctoutputoctets ?? 0),
                'upload' => (int) ($s->acctinputoctets ?? 0),
                'login_time' => $s->acctstarttime ? $s->acctstarttime->toIso8601String() : null,
                'is_active' => (bool) $v->is_active,
                'status' => 'online',
            ]);
        }

        // 4. Build Offline List:
        // KETENTUAN:
        // 1. Voucher offline adalah voucher yang SUDAH LOGIN dan SEMPAT TIDAK DI PAKAI (offline).
        // 2. Voucher EXPIRED TIDAK BOLEH masuk ke voucher offline (masuk ke menu Voucher Expired).
        // 3. STOK VOUCHER yang belum dipakai (status available/belum pernah login) TIDAK BOLEH masuk.
        $offlineCandidates = $allVouchers->filter(function($v) use ($onlineUsernames) {
            // Jangan masukkan yang sedang online
            if (in_array($v->username, $onlineUsernames) || in_array($v->code, $onlineUsernames)) {
                return false;
            }
            // Stok voucher yang belum dipakai (available) -> JANGAN MASUK
            if ($v->status !== 'used') {
                return false;
            }
            // Voucher expired -> JANGAN MASUK
            if ($v->status === 'expired') {
                return false;
            }
            // Cek masa aktif jika ada limit durasi
            if ($v->profile && $v->profile->duration && $v->profile->duration !== 'Mengikuti Pelanggan') {
                $durationLimit = \App\Services\RadiusService::parseDuration($v->profile->duration);
                if ($durationLimit && $v->used_at) {
                    $expireAt = \Carbon\Carbon::parse($v->used_at)->addSeconds($durationLimit);
                    if (now()->greaterThanOrEqualTo($expireAt)) {
                        $v->status = 'expired';
                        $v->save();
                        return false;
                    }
                }
            }
            return true;
        });

        // Ambil riwayat sesi RADIUS untuk voucher offline ini
        $voucherUsernames = $offlineCandidates->flatMap(fn($v) => [$v->username, $v->code])->filter()->unique()->toArray();
        $offlineSessionsMap = collect();
        if (!empty($voucherUsernames)) {
            try {
                $offlineSessionsMap = \App\Models\Radius\RadAcct::whereIn('username', $voucherUsernames)
                    ->whereNotNull('acctstoptime')
                    ->orderByDesc('acctstoptime')
                    ->get()
                    ->groupBy('username');
            } catch (\Exception $e) {
                $offlineSessionsMap = collect();
            }
        }

        $offlineList = collect();
        foreach ($offlineCandidates as $v) {
            $userSessions = $offlineSessionsMap->get($v->username) ?? $offlineSessionsMap->get($v->code);
            $lastSession = $userSessions?->first();

            // Jika sesi terakhir di RADIUS diputus karena masa aktif habis (Session-Timeout / Expired), tandai expired & lewati
            if ($lastSession && in_array(strtolower($lastSession->acctterminatecause ?? ''), ['session-timeout', 'expired'])) {
                $v->status = 'expired';
                $v->save();
                continue;
            }

            // Hitung akumulasi penggunaan sesi
            $totalTime = $userSessions ? $userSessions->sum('acctsessiontime') : 0;
            $download = $userSessions ? $userSessions->sum('acctoutputoctets') : 0;
            $upload = $userSessions ? $userSessions->sum('acctinputoctets') : 0;

            // Cek jika akumulasi durasi pemakaian sudah mencapai batas
            if ($v->profile && $v->profile->duration && $v->profile->duration !== 'Mengikuti Pelanggan') {
                $durationLimit = \App\Services\RadiusService::parseDuration($v->profile->duration);
                if ($durationLimit && $totalTime >= $durationLimit) {
                    $v->status = 'expired';
                    $v->save();
                    continue;
                }
            }

            if (!$matchesFilter($v, $lastSession, $search, $profileId, $resellerId, $areaId)) {
                continue;
            }

            $resellerCustomer = $v->reseller?->customer;
            $areaName = $resellerCustomer?->areaModel?->name ?? ($resellerCustomer?->area ?: ($v->customer?->areaModel?->name ?? ($v->customer?->area ?: '-')));

            $offlineList->push([
                'id' => $v->id,
                'voucher_id' => $v->id,
                'code' => $v->code,
                'username' => $v->username,
                'profile' => $v->profile?->name ?? 'Hotspot Profile',
                'profile_duration' => $v->profile?->duration,
                'reseller' => $resellerCustomer?->name ?? 'Kantor / System',
                'area' => $areaName,
                'ip_address' => $lastSession?->framedipaddress ?? '-',
                'mac_address' => $lastSession?->callingstationid ?? '-',
                'nas_ip' => $lastSession?->nasipaddress ?? '-',
                'login_time' => $lastSession?->acctstarttime ? $lastSession->acctstarttime->toIso8601String() : ($v->used_at ? $v->used_at->toIso8601String() : null),
                'logout_time' => $lastSession?->acctstoptime ? $lastSession->acctstoptime->toIso8601String() : null,
                'total_time' => (int) $totalTime,
                'download' => (int) $download,
                'upload' => (int) $upload,
                'terminate_cause' => $lastSession?->acctterminatecause ?? 'Selesai / Logout',
                'is_active' => (bool) $v->is_active,
                'status' => 'offline',
            ]);
        }

        // 5. Aggregate Statistics
        $totalDownloadBytes = $onlineList->sum('download') + $offlineList->sum('download');
        $totalUploadBytes = $onlineList->sum('upload') + $offlineList->sum('upload');

        $stats = [
            'online_count' => $onlineList->count(),
            'offline_count' => $offlineList->count(),
            'total_count' => $onlineList->count() + $offlineList->count(),
            'total_download' => $totalDownloadBytes,
            'total_upload' => $totalUploadBytes,
        ];

        return Inertia::render('Vouchers/Online', [
            'tab' => $tab,
            'onlineUsers' => $onlineList->values(),
            'offlineUsers' => $offlineList->values(),
            'stats' => $stats,
            'profiles' => $profiles,
            'resellers' => $resellers,
            'areas' => $areas,
            'filters' => [
                'tab' => $tab,
                'search' => $search ?: '',
                'profile_id' => $profileId ?: '',
                'reseller_id' => $resellerId ?: '',
                'area_id' => $areaId ?: '',
            ],
            'isResellerPortal' => $isResellerPortal,
        ]);
    }

    public function offline(Request $request)
    {
        return redirect()->route('vouchers.online', array_merge($request->all(), ['tab' => 'offline']));
    }

    public function expired(Request $request)
    {
        // 0. Otomatis cek dan sinkronkan status voucher yang durasinya habis menjadi expired
        self::checkAndExpireVouchers();

        $user = auth()->user();
        $isResellerPortal = $user && (strtolower($user->role ?? '') === 'reseller' || session('login_portal') === 'reseller');

        $vQuery = Voucher::with(['profile', 'reseller.customer.areaModel', 'customer.areaModel'])
            ->where('status', 'expired');
        $this->applyResellerFilter($vQuery);
        $vouchers = $vQuery->latest('vouchers.id')->get();

        $usernames = $vouchers->pluck('username')->filter()->unique()->toArray();
        $accts = !empty($usernames) 
            ? \App\Models\Radius\RadAcct::whereIn('username', $usernames)->get()->groupBy('username')
            : collect();

        $rows = $vouchers->map(function ($v) use ($accts) {
            $resellerCust = $v->reseller?->customer;
            $areaName = $resellerCust?->areaModel?->name ?? ($resellerCust?->area ?: ($v->customer?->areaModel?->name ?? ($v->customer?->area ?: '')));
            $userAccts = $accts->get($v->username);
            $totalTime = $userAccts ? (int) $userAccts->sum('acctsessiontime') : 0;
            $firstLogin = $userAccts ? $userAccts->min('acctstarttime') : null;
            $expiredAt = $v->used_at ?: ($userAccts ? $userAccts->max('acctstoptime') : null);

            return [
                'id' => $v->id,
                'code' => $v->code,
                'username' => $v->username,
                'password' => $v->password,
                'profile' => $v->profile?->name ?? 'Hotspot Profile',
                'duration' => $v->profile?->duration ?? '',
                'reseller' => $resellerCust?->name ?? 'Kantor / System',
                'area' => $areaName,
                'is_free' => (bool) $v->is_free,
                'first_login' => $firstLogin ? \Carbon\Carbon::parse($firstLogin)->toIso8601String() : null,
                'expired_at' => $expiredAt ? \Carbon\Carbon::parse($expiredAt)->toIso8601String() : null,
                'used_at' => $v->used_at ? $v->used_at->toIso8601String() : null,
                'total_time' => $totalTime,
                'is_active' => (bool) $v->is_active,
            ];
        });

        if ($isResellerPortal && $user) {
            $profiles = $user->voucherProfiles()->where('voucher_profiles.is_customer_free', false)
                ->select('voucher_profiles.id', 'voucher_profiles.name')
                ->get();
            if ($profiles->isEmpty()) {
                $profiles = VoucherProfile::where('is_customer_free', false)->orderBy('name')->get(['id', 'name']);
            }
        } else {
            $profiles = VoucherProfile::orderBy('name')->get(['id', 'name']);
        }

        $resellers = \App\Models\Reseller::with('customer.areaModel')->get();

        return Inertia::render('Vouchers/Expired', [
            'expiredUsers' => $rows->values(),
            'profiles' => $profiles,
            'resellers' => $resellers,
            'isResellerPortal' => $isResellerPortal,
        ]);
    }

    /**
     * Dapatkan data reseller atau inisialisasi otomatis untuk admin / role reseller.
     */
    private function getOrCreateReseller($user)
    {
        if (!$user) return null;

        $reseller = \App\Models\Reseller::where('user_id', $user->id)->first();
        if (!$reseller) {
            $userRole = strtolower(trim((string) $user->role));
            $isAdminLike = in_array($userRole, ['admin', 'superadmin', 'super admin', 'super-admin']) 
                || (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super admin')));
            $isResellerRole = in_array($userRole, ['reseller']) 
                || (method_exists($user, 'hasRole') && $user->hasRole('reseller'))
                || session('login_portal') === 'reseller';

            if ($isAdminLike || $isResellerRole) {
                $reseller = \App\Models\Reseller::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'customer_id' => $user->customer?->id ?? null,
                        'balance' => $isAdminLike ? 5000000 : 0,
                        'is_active' => true,
                    ]
                );
            }
        }
        return $reseller;
    }

    private function applyResellerFilter($query)
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        $isAdminLike = $user->isAdmin()
            || in_array(strtolower($user->role ?? ''), ['admin', 'super admin', 'superadmin', 'demo', 'management', 'keuangan'])
            || (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super admin') || $user->hasRole('superadmin')));

        // Admin di panel utama (bukan portal reseller) melihat semua voucher
        if ($isAdminLike && session('login_portal') !== 'reseller') {
            return;
        }

        // Jika portal reseller atau role reseller, filter hanya voucher miliknya
        $reseller = $this->getOrCreateReseller($user);
        if ($reseller) {
            $query->where('vouchers.reseller_id', $reseller->id);
        } else {
            $query->where('vouchers.id', -1);
        }
    }

    public function store(Request $request)
    {
        $mode = $request->input('mode', 'bulk');
        $isFree = $request->boolean('is_free', false) || $request->input('cost_type') === 'free';
        $user = auth()->user();

        $isAdminLike = $user && ($user->isAdmin() || in_array(strtolower($user->role ?? ''), ['admin', 'super admin', 'superadmin', 'demo']));
        $isResellerUser = $user && (
            strtolower($user->role ?? '') === 'reseller'
            || strtolower($user->role ?? '') === 'biller'
            || session('login_portal') === 'reseller'
            || ($user->reseller && !$isAdminLike)
        );

        // Reseller atau biller tidak boleh membuat voucher gratis sama sekali
        if ($isResellerUser) {
            $isFree = false;
        }

        if ($isFree && $user && !$user->isAdmin() && !$user->can('vouchers_free_create') && !$user->can('vouchers_create')) {
            return redirect()->back()->withErrors(['voucher_profile_id' => 'Anda tidak memiliki hak akses untuk mengenerate voucher gratis.'])->withInput();
        }

        // ── Single Voucher ──────────────────────────────────────────
        if ($mode === 'single') {
            $validated = $request->validate([
                'voucher_profile_id' => 'required|exists:voucher_profiles,id',
                'username' => 'nullable|string|max:50',
                'password' => 'nullable|string|max:50',
                'buyer_name' => 'nullable|string|max:100',
                'buyer_phone' => 'nullable|string|max:30',
                'login_type' => 'nullable|in:username_only,username_password',
                'reseller_id' => 'nullable|exists:resellers,id',
                'customer_id' => 'nullable|exists:customers,id',
                'is_free' => 'nullable|boolean',
                'cost_type' => 'nullable|string',
            ]);

            $profile = VoucherProfile::findOrFail($validated['voucher_profile_id']);
            if ($isResellerUser && ($profile->price <= 0 || $profile->is_customer_free)) {
                return redirect()->back()->withErrors(['voucher_profile_id' => 'Paket voucher gratis tidak dapat dijual oleh reseller.'])->withInput();
            }
            if ($profile->price <= 0 || $profile->is_customer_free) {
                $isFree = true;
            }

            // Generate or use custom username
            if (!empty($validated['username'])) {
                $username = strtoupper(trim($validated['username']));
            } else {
                $prefix = $isFree ? 'FREE-' : 'V-';
                $username = $prefix . strtoupper(Str::random(6));
            }

            while (Voucher::where('code', $username)->exists()) {
                $username = ($isFree ? 'FREE-' : 'V-') . strtoupper(Str::random(6));
            }

            if (($validated['login_type'] ?? '') === 'username_only' || empty($validated['password'])) {
                $password = $username;
            } else {
                $password = trim($validated['password']);
            }

            $targetResellerId = $validated['reseller_id'] ?? null;
            if (!$targetResellerId && $isResellerUser) {
                $autoReseller = $this->getOrCreateReseller($user);
                if ($autoReseller) {
                    $targetResellerId = $autoReseller->id;
                }
            }

            $reseller = null;
            $resellerPayment = $request->input('reseller_payment', 'balance');
            if (!empty($targetResellerId) && !$isFree) {
                $reseller = Reseller::find($targetResellerId);
                $costPerVoucher = max(0, (float)$profile->price - (float)($profile->fee_reseller ?? 0));
                if ($reseller && $resellerPayment === 'balance') {
                    if ($reseller->balance < $costPerVoucher) {
                        return redirect()->back()->withErrors(['reseller_id' => 'Saldo reseller tidak mencukupi (Butuh Rp ' . number_format($costPerVoucher, 0, ',', '.') . ', Saldo: Rp ' . number_format($reseller->balance, 0, ',', '.') . ').'])->withInput();
                    }
                    $reseller->balance -= $costPerVoucher;
                    $reseller->save();
                    \App\Models\ResellerTransaction::create([
                        'reseller_id' => $reseller->id,
                        'type' => 'debit',
                        'amount' => $costPerVoucher,
                        'description' => "Pembelian 1 Voucher {$profile->name}",
                        'reference_id' => 'VOUCHER-SINGLE',
                    ]);
                }
            }

            $voucher = Voucher::create([
                'voucher_profile_id' => $profile->id,
                'reseller_id' => !empty($targetResellerId) ? $targetResellerId : null,
                'customer_id' => !empty($validated['customer_id']) ? $validated['customer_id'] : null,
                'code' => $username,
                'username' => $username,
                'password' => $password,
                'buyer_name' => !empty($validated['buyer_name']) ? trim($validated['buyer_name']) : null,
                'buyer_phone' => !empty($validated['buyer_phone']) ? trim($validated['buyer_phone']) : null,
                'status' => 'available',
                'is_active' => true,
                'is_free' => $isFree,
                'payment_method' => $isFree ? 'free' : (!empty($targetResellerId) ? ($resellerPayment === 'cash' ? 'cash' : 'balance') : 'system'),
            ]);

            try {
                app(RadiusService::class)->syncVouchers(collect([$voucher]));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Radius sync error: " . $e->getMessage());
            }

            $msg = $isFree 
                ? "Voucher Gratis {$username} ({$profile->name}) berhasil dibuat." 
                : "Voucher {$username} ({$profile->name}) berhasil dibuat.";
            return redirect()->back()->with('success', $msg);
        }

        // ── Bulk / Massal Voucher ──────────────────────────────────
        $validated = $request->validate([
            'voucher_profile_id' => 'required|exists:voucher_profiles,id',
            'amount' => 'required|integer|min:1|max:500',
            'length' => 'required|integer|min:4|max:12',
            'prefix' => 'nullable|string|max:10',
            'type' => 'required|in:up,vc', // up = user & password, vc = code only (user=pass=code)
            'reseller_id' => 'nullable|exists:resellers,id',
            'customer_id' => 'nullable|exists:customers,id',
            'combination' => 'nullable|string|in:alphanumeric,numeric,alpha',
            'is_free' => 'nullable|boolean',
            'cost_type' => 'nullable|string',
        ]);

        $profile = VoucherProfile::findOrFail($validated['voucher_profile_id']);
        if ($isResellerUser && ($profile->price <= 0 || $profile->is_customer_free)) {
            return redirect()->back()->withErrors(['voucher_profile_id' => 'Paket voucher gratis tidak dapat dijual oleh reseller.'])->withInput();
        }
        if ($profile->price <= 0 || $profile->is_customer_free) {
            $isFree = true;
        }

        $targetResellerId = $validated['reseller_id'] ?? null;
        if (!$targetResellerId && $isResellerUser) {
            $autoReseller = $this->getOrCreateReseller($user);
            if ($autoReseller) {
                $targetResellerId = $autoReseller->id;
            }
        }

        $reseller = null;
        $resellerPayment = $request->input('reseller_payment', 'balance');
        if (!empty($targetResellerId) && !$isFree) {
            $reseller = Reseller::find($targetResellerId);
            $costPerVoucher = max(0, (float)$profile->price - (float)($profile->fee_reseller ?? 0));
            $totalPrice = $costPerVoucher * $validated['amount'];

            if ($reseller && $resellerPayment === 'balance') {
                if ($reseller->balance < $totalPrice) {
                    return redirect()->back()->withErrors(['reseller_id' => 'Saldo reseller tidak mencukupi (Butuh Rp ' . number_format($totalPrice, 0, ',', '.') . ', Saldo: Rp ' . number_format($reseller->balance, 0, ',', '.') . ').'])->withInput();
                }

                $reseller->balance -= $totalPrice;
                $reseller->save();

                \App\Models\ResellerTransaction::create([
                    'reseller_id' => $reseller->id,
                    'type' => 'debit',
                    'amount' => $totalPrice,
                    'description' => "Pembelian {$validated['amount']} Voucher {$profile->name} (Modal: Rp " . number_format($costPerVoucher, 0, ',', '.') . "/voucher)",
                    'reference_id' => 'VOUCHER-GEN',
                ]);
            }
        }

        $vouchers = [];
        $now = now();
        $combination = $validated['combination'] ?? 'alphanumeric';

        $generateString = function($len, $comb) {
            if ($comb === 'numeric') {
                return substr(str_shuffle(str_repeat('0123456789', $len)), 0, $len);
            } elseif ($comb === 'alpha') {
                return substr(str_shuffle(str_repeat('ABCDEFGHIJKLMNOPQRSTUVWXYZ', $len)), 0, $len);
            } else {
                return strtoupper(Str::random($len));
            }
        };

        for ($i = 0; $i < $validated['amount']; $i++) {
            $prefix = $validated['prefix'] ?? ($isFree ? 'FREE-' : '');
            $len = $validated['length'];

            $code = strtoupper($prefix . $generateString($len, $combination));
            while (Voucher::where('code', $code)->exists()) {
                $code = strtoupper($prefix . $generateString($len, $combination));
            }

            if ($validated['type'] === 'vc') {
                $username = $code;
                $password = $code;
            } else {
                $username = $code;
                $password = $generateString($len, $combination);
            }

            $vouchers[] = [
                'voucher_profile_id' => $profile->id,
                'reseller_id' => !empty($targetResellerId) ? $targetResellerId : null,
                'customer_id' => !empty($validated['customer_id']) ? $validated['customer_id'] : null,
                'code' => $code,
                'username' => $username,
                'password' => $password,
                'status' => 'available',
                'is_active' => true,
                'is_free' => $isFree,
                'payment_method' => $isFree ? 'free' : (!empty($targetResellerId) ? ($resellerPayment === 'cash' ? 'cash' : 'balance') : 'system'),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Voucher::insert($vouchers);

        if (!empty($vouchers)) {
            $createdVouchers = Voucher::whereIn('code', array_column($vouchers, 'code'))->get();
            try {
                app(RadiusService::class)->syncVouchers($createdVouchers);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Radius sync error: " . $e->getMessage());
            }
        }

        $msg = $isFree 
            ? "{$validated['amount']} Voucher Gratis ({$profile->name}) berhasil di-generate." 
            : "{$validated['amount']} Voucher ({$profile->name}) berhasil di-generate.";

        return redirect()->back()->with('success', $msg);
    }

    public function generateForCustomer(Request $request, Customer $customer)
    {
        $user = auth()->user();
        if ($user && !$user->isAdmin() && !$user->can('vouchers_free_sync_customer') && !$user->can('vouchers_free_create')) {
            return redirect()->back()->withErrors(['message' => 'Anda tidak memiliki izin untuk menyinkronkan voucher gratis pelanggan.']);
        }

        $profileId = $request->voucher_profile_id;
        $profile = $profileId ? VoucherProfile::find($profileId) : VoucherProfile::where('is_customer_free', true)->first();
        if (!$profile) {
            $profile = VoucherProfile::where('price', 0)->first() ?? VoucherProfile::first();
        }

        $username = $customer->ont?->hotspot_user ?: 'HOTSPOT-' . strtoupper(Str::random(6));
        $password = $customer->ont?->hotspot_password ?: $username;

        $voucher = Voucher::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'is_free' => true,
            ],
            [
                'voucher_profile_id' => $profile->id,
                'code' => $username,
                'username' => $username,
                'password' => $password,
                'status' => $customer->status === 'active' ? 'used' : 'available',
                'is_active' => true,
                'payment_method' => 'free',
                'used_at' => $customer->status === 'active' ? now() : null,
            ]
        );

        try {
            app(RadiusService::class)->syncVouchers(collect([$voucher]));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Radius sync error: " . $e->getMessage());
        }

        return redirect()->back()->with('success', "Voucher Gratis untuk pelanggan {$customer->name} ({$username}) berhasil disinkronkan.");
    }

    public function destroy(Voucher $voucher)
    {
        $user = auth()->user();
        $isAdminLike = $user && ($user->isAdmin() || in_array(strtolower($user->role ?? ''), ['admin', 'super admin', 'superadmin', 'demo']));
        if (!$isAdminLike && $voucher->reseller?->user_id !== $user?->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus voucher ini.');
        }

        $voucher->delete();
        return redirect()->back()->with('success', 'Voucher berhasil dihapus.');
    }

    public function toggleStatus(Voucher $voucher)
    {
        $user = auth()->user();
        $isAdminLike = $user && ($user->isAdmin() || in_array(strtolower($user->role ?? ''), ['admin', 'super admin', 'superadmin', 'demo']));
        if (!$isAdminLike && $voucher->reseller?->user_id !== $user?->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah status voucher ini.');
        }

        $voucher->is_active = !$voucher->is_active;
        $voucher->save(); // This triggers Radius sync via VoucherObserver

        if (!$voucher->is_active) {
            // Kick user if they are currently online
            app(\App\Services\RadiusService::class)->disconnect($voucher->username);
        }

        $status = $voucher->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->back()->with('success', "Voucher {$voucher->code} berhasil {$status}.");
    }
    
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:vouchers,id',
        ]);
        
        $query = Voucher::whereIn('id', $validated['ids']);
        $this->applyResellerFilter($query);
        $vouchersToDelete = $query->get();
        
        if ($vouchersToDelete->isEmpty()) {
            return redirect()->back()->withErrors(['message' => 'Tidak ada voucher yang dapat dihapus.']);
        }

        Voucher::whereIn('id', $vouchersToDelete->pluck('id'))->delete();
        
        // Hapus dari RADIUS secara eksplisit
        $radius = app(RadiusService::class);
        foreach ($vouchersToDelete as $v) {
            $radius->removeUser($v->username);
        }
        
        return redirect()->back()->with('success', count($vouchersToDelete) . ' Voucher berhasil dihapus.');
    }

    public function assignBuyer(Request $request, Voucher $voucher)
    {
        $user = auth()->user();
        $isAdminLike = $user && ($user->isAdmin() || in_array(strtolower($user->role ?? ''), ['admin', 'super admin', 'superadmin', 'demo']));
        if (!$isAdminLike && $voucher->reseller?->user_id !== $user?->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah voucher ini.');
        }

        $validated = $request->validate([
            'buyer_name' => 'nullable|string|max:100',
            'buyer_phone' => 'nullable|string|max:30',
        ]);

        $voucher->update([
            'buyer_name' => !empty($validated['buyer_name']) ? trim($validated['buyer_name']) : null,
            'buyer_phone' => !empty($validated['buyer_phone']) ? trim($validated['buyer_phone']) : null,
        ]);

        return redirect()->back()->with('success', "Data pembeli voucher {$voucher->code} berhasil diperbarui.");
    }

    public function print(Request $request)
    {
        $rawIds = $request->input('ids');
        if (is_string($rawIds)) {
            $ids = array_filter(explode(',', $rawIds));
        } else {
            $ids = (array) $rawIds;
        }

        $query = Voucher::with('profile');
        if (!empty($ids)) {
            $query->whereIn('vouchers.id', $ids);
        }

        $this->applyResellerFilter($query);
        $vouchers = $query->latest('vouchers.id')->get();

        return view('vouchers.print', compact('vouchers'));
    }
}
