<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\VoucherProfile;
use App\Services\RadiusService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    public function free(Request $request)
    {
        $query = \App\Models\Customer::with(['ont', 'package', 'areaModel'])
            ->whereHas('ont', function($q) {
                $q->where('free_hotspot', true);
            });
            
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%")
                  ->orWhereHas('ont', function($subQ) use ($search) {
                      $subQ->where('hotspot_user', 'like', "%{$search}%");
                  });
            });
        }
        
        $customers = $query->latest()->paginate(15)->withQueryString();
        $freeProfile = \App\Models\VoucherProfile::where('is_customer_free', true)->first();
        
        return Inertia::render('Vouchers/Free/Index', [
            'customers' => $customers,
            'freeProfile' => $freeProfile,
            'filters' => $request->only('search')
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
        // 1. Jalankan pengecekan voucher expired terlebih dahulu
        self::checkAndExpireVouchers();

        $query = Voucher::with(['profile', 'reseller.customer']);

        if ($request->filled('profile_id')) {
            $query->where('voucher_profile_id', $request->profile_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Secara default di menu Data Voucher, kecualikan voucher yang expired
            // (Voucher expired dipindahkan ke menu 'Voucher Expired')
            $query->where('status', '!=', 'expired');
        }
        
        if ($request->filled('reseller_id')) {
            if ($request->reseller_id === 'system') {
                $query->whereNull('reseller_id');
            } else {
                $query->where('reseller_id', $request->reseller_id);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhereHas('reseller.customer', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        $this->applyResellerFilter($query);

        $vouchers = $query->latest()->paginate(20)->withQueryString();
        $user = auth()->user();
        if ($user->isAdmin()) {
            $profiles = VoucherProfile::where('is_customer_free', false)->get();
        } else {
            $profiles = $user->voucherProfiles()->where('is_customer_free', false)->get();
        }
        $resellers = \App\Models\Reseller::with('customer')->get();

        return Inertia::render('Vouchers/Index', [
            'vouchers' => $vouchers,
            'profiles' => $profiles,
            'resellers' => $resellers,
            'filters' => $request->only(['search', 'profile_id', 'status', 'reseller_id']),
            'onlineUsernames' => (function() {
                try {
                    return \App\Models\Radius\RadAcct::online()->pluck('username')->toArray();
                } catch (\Exception $e) {
                    return [];
                }
            })(),
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
                        
                        // Hitung total time menggunakan liveSessionTime()
                        $stats[$user]->total_time += $record->liveSessionTime();
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
        try {
            $sessions = \App\Models\Radius\RadAcct::online()
                ->orderByDesc('acctstarttime')
                ->get(['radacctid', 'username', 'framedipaddress', 'callingstationid', 'nasipaddress', 'acctstarttime', 'acctsessiontime', 'acctinputoctets', 'acctoutputoctets'])
                ->unique('username')
                ->keyBy('username');
        } catch (\Exception $e) {
            $sessions = collect();
        }

        $vouchers = collect();
        if ($sessions->isNotEmpty()) {
            $sessionKeys = $sessions->keys()->all();
            $vQuery = Voucher::with('profile', 'reseller.customer.areaModel')
                ->where(function ($q) use ($sessionKeys) {
                    $q->whereIn('username', $sessionKeys)
                      ->orWhereIn('code', $sessionKeys);
                });
            $this->applyResellerFilter($vQuery);
            $vouchers = $vQuery->get();
        }

        // Map data
        $rows = $vouchers->map(function ($v) use ($sessions) {
            $s = $sessions->get($v->username) ?? $sessions->get($v->code);
            if (!$s) return null;
            $resellerCustomer = $v->reseller?->customer;
            return [
                'id' => $v->id,
                'code' => $v->code,
                'username' => $v->username,
                'profile' => $v->profile?->name,
                'reseller' => $resellerCustomer?->name ?? 'Admin',
                'area' => $resellerCustomer?->areaModel?->name ?? ($resellerCustomer?->area ?: null),
                'ip_address' => $s->framedipaddress,
                'mac_address' => $s->callingstationid,
                'nas_ip' => $s->nasipaddress,
                'uptime' => method_exists($s, 'liveSessionTime') ? $s->liveSessionTime() : ($s->acctsessiontime ?: 0),
                'download' => $s->acctoutputoctets,
                'upload' => $s->acctinputoctets,
                'login_time' => $s->acctstarttime ? $s->acctstarttime->toIso8601String() : null,
                'is_active' => $v->is_active,
            ];
        })->filter();

        return Inertia::render('Vouchers/Online', [
            'onlineUsers' => $rows->values()
        ]);
    }

    public function offline(Request $request)
    {
        try {
            $onlineUsernames = \App\Models\Radius\RadAcct::online()->pluck('username')->toArray();
            
            // Get latest offline session for each voucher
            $latestSessions = \App\Models\Radius\RadAcct::whereNotIn('username', $onlineUsernames)
                ->whereNotNull('acctstoptime')
                ->orderByDesc('acctstoptime')
                ->get()
                ->unique('username')
                ->keyBy('username');
        } catch (\Exception $e) {
            $onlineUsernames = [];
            $latestSessions = collect();
        }

        $vQuery = Voucher::with('profile', 'reseller.customer')
            ->whereNotIn('username', $onlineUsernames);
        $this->applyResellerFilter($vQuery);
        $vouchers = $vQuery->get();

        // Map data
        $rows = $vouchers->map(function ($v) use ($latestSessions) {
            $s = $latestSessions->get($v->username);
            return [
                'id' => $v->id,
                'code' => $v->code,
                'username' => $v->username,
                'profile' => $v->profile?->name,
                'reseller' => $v->reseller?->customer?->name ?? 'Admin',
                'ip_address' => $s?->framedipaddress,
                'mac_address' => $s?->callingstationid,
                'last_logout' => $s?->acctstoptime ? $s->acctstoptime->toIso8601String() : null,
                'total_time' => $s?->acctsessiontime,
                'is_active' => $v->is_active,
            ];
        });

        return Inertia::render('Vouchers/Offline', [
            'offlineUsers' => $rows->values()
        ]);
    }

    public function expired(Request $request)
    {
        // 1. Jalankan pengecekan voucher expired terlebih dahulu
        self::checkAndExpireVouchers();

        $vQuery = Voucher::with(['profile', 'reseller.customer.areaModel'])
            ->where('status', 'expired');

        $this->applyResellerFilter($vQuery);

        if ($request->filled('profile_id')) {
            $vQuery->where('voucher_profile_id', $request->profile_id);
        }

        if ($request->filled('reseller_id')) {
            if ($request->reseller_id === 'system') {
                $vQuery->whereNull('reseller_id');
            } else {
                $vQuery->where('reseller_id', $request->reseller_id);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $vQuery->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhereHas('reseller.customer', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $vouchers = $vQuery->latest('updated_at')->get();

        $usernames = $vouchers->pluck('username')->filter()->unique()->toArray();
        $records = !empty($usernames) 
            ? \App\Models\Radius\RadAcct::whereIn('username', $usernames)->get()->groupBy('username') 
            : collect();

        $rows = $vouchers->map(function ($v) use ($records) {
            $resellerCustomer = $v->reseller?->customer;
            $userAccts = $records->get($v->username);

            $totalTime = 0;
            $firstLogin = null;
            $lastLogout = null;
            if ($userAccts) {
                foreach ($userAccts as $acct) {
                    if (!$firstLogin || $acct->acctstarttime < $firstLogin) {
                        $firstLogin = $acct->acctstarttime;
                    }
                    if (!$lastLogout || $acct->acctstoptime > $lastLogout) {
                        $lastLogout = $acct->acctstoptime;
                    }
                    $totalTime += $acct->liveSessionTime();
                }
            }

            $expiredAt = $v->used_at ?: ($lastLogout ?: $v->updated_at);

            return [
                'id' => $v->id,
                'code' => $v->code,
                'username' => $v->username,
                'password' => $v->password,
                'profile' => $v->profile?->name,
                'duration' => $v->profile?->duration,
                'is_free' => (bool) $v->is_free,
                'reseller' => $resellerCustomer?->name ?? 'Kantor / System',
                'area' => $resellerCustomer?->areaModel?->name ?? ($resellerCustomer?->area ?: null),
                'first_login' => $firstLogin ? \Carbon\Carbon::parse($firstLogin)->toIso8601String() : null,
                'expired_at' => $expiredAt ? \Carbon\Carbon::parse($expiredAt)->toIso8601String() : null,
                'used_at' => $expiredAt ? \Carbon\Carbon::parse($expiredAt)->toIso8601String() : null,
                'total_time' => $totalTime,
                'is_active' => $v->is_active,
            ];
        });

        $user = auth()->user();
        if ($user && $user->isAdmin()) {
            $profiles = VoucherProfile::all();
        } else {
            $profiles = $user ? $user->voucherProfiles()->get() : collect();
        }
        $resellers = \App\Models\Reseller::with('customer')->get();

        return Inertia::render('Vouchers/Expired', [
            'expiredUsers' => $rows->values(),
            'profiles' => $profiles,
            'resellers' => $resellers,
            'filters' => $request->only(['search', 'profile_id', 'reseller_id']),
        ]);
    }

    private function applyResellerFilter($query)
    {
        $user = auth()->user();
        if ($user && !$user->isAdmin()) {
            $reseller = \App\Models\Reseller::where('user_id', $user->id)->first();
            if ($reseller) {
                $query->where('reseller_id', $reseller->id);
            } else {
                $query->where('id', -1);
            }
        }
    }

    public function store(Request $request)
    {
        $mode = $request->input('mode', 'bulk');

        if ($mode === 'single') {
            $validated = $request->validate([
                'voucher_profile_id' => 'required|exists:voucher_profiles,id',
                'username' => 'required|string|min:3|max:50|unique:vouchers,username',
                'password' => 'nullable|string|max:50',
                'login_type' => 'required|in:username_only,username_password',
                'cost_type' => 'required|in:paid,free',
                'reseller_id' => 'nullable|exists:resellers,id',
                'reseller_payment' => 'nullable|in:balance,cash',
            ]);

            $profile = VoucherProfile::where('is_customer_free', false)->findOrFail($validated['voucher_profile_id']);
            $isFree = $validated['cost_type'] === 'free';
            $paymentMethod = $isFree ? 'free' : 'system';

            $reseller = null;
            if (!empty($validated['reseller_id'])) {
                $reseller = \App\Models\Reseller::findOrFail($validated['reseller_id']);
                
                if ($isFree) {
                    $paymentMethod = 'free';
                } else {
                    $paymentOption = $validated['reseller_payment'] ?? 'balance';
                    if ($paymentOption === 'balance') {
                        $fee = $profile->fee_reseller;
                        if ($reseller->balance < $fee) {
                            return redirect()->back()->withErrors([
                                'reseller_id' => 'Saldo reseller tidak mencukupi (Butuh Rp ' . number_format($fee, 0, ',', '.') . ').'
                            ])->withInput();
                        }
                        $reseller->balance -= $fee;
                        $reseller->save();

                        \App\Models\ResellerTransaction::create([
                            'reseller_id' => $reseller->id,
                            'type' => 'debit',
                            'amount' => $fee,
                            'description' => 'Pembelian 1 Voucher ' . $profile->name . ' (' . $validated['username'] . ')',
                            'reference_id' => 'VOUCHER-SINGLE',
                        ]);
                        $paymentMethod = 'balance';
                    } else {
                        // Bayar tunai di kantor / non-saldo
                        $paymentMethod = 'cash';
                    }
                }
            }

            $username = trim($validated['username']);
            $password = $validated['login_type'] === 'username_only'
                ? $username
                : (!empty($validated['password']) ? trim($validated['password']) : $username);

            $voucher = Voucher::create([
                'voucher_profile_id' => $profile->id,
                'reseller_id' => $reseller ? $reseller->id : null,
                'code' => $username,
                'username' => $username,
                'password' => $password,
                'status' => 'available',
                'is_free' => $isFree,
                'payment_method' => $paymentMethod,
                'is_active' => true,
            ]);

            // Sync ke RADIUS
            app(RadiusService::class)->syncVouchers([$voucher]);

            $typeLabel = $isFree ? 'Gratis' : 'Berbayar';
            return redirect()->back()->with('success', "Single Voucher {$voucher->code} ({$typeLabel}) berhasil dibuat.");
        }

        // Mode Bulk
        $validated = $request->validate([
            'voucher_profile_id' => 'required|exists:voucher_profiles,id',
            'amount' => 'required|integer|min:1|max:500',
            'length' => 'required|integer|min:4|max:12',
            'prefix' => 'nullable|string|max:4',
            'type' => 'required|in:up,vc', // up = user & password, vc = code only (user=pass=code)
            'cost_type' => 'nullable|in:paid,free',
            'reseller_id' => 'nullable|exists:resellers,id',
            'reseller_payment' => 'nullable|in:balance,cash',
            'combination' => 'nullable|string|in:alphanumeric,numeric,alpha',
        ]);

        $profile = VoucherProfile::where('is_customer_free', false)->findOrFail($validated['voucher_profile_id']);
        $isFree = ($validated['cost_type'] ?? 'paid') === 'free';
        $paymentMethod = $isFree ? 'free' : 'system';

        $reseller = null;
        if (!empty($validated['reseller_id'])) {
            $reseller = \App\Models\Reseller::findOrFail($validated['reseller_id']);
            
            if ($isFree) {
                $paymentMethod = 'free';
            } else {
                $paymentOption = $validated['reseller_payment'] ?? 'balance';
                if ($paymentOption === 'balance') {
                    $totalPrice = $profile->fee_reseller * $validated['amount'];
                    if ($reseller->balance < $totalPrice) {
                        return redirect()->back()->withErrors([
                            'reseller_id' => 'Saldo reseller tidak mencukupi (Butuh Rp ' . number_format($totalPrice, 0, ',', '.') . ').'
                        ])->withInput();
                    }

                    // Potong saldo reseller
                    $reseller->balance -= $totalPrice;
                    $reseller->save();

                    // Catat transaksi mutasi
                    \App\Models\ResellerTransaction::create([
                        'reseller_id' => $reseller->id,
                        'type' => 'debit',
                        'amount' => $totalPrice,
                        'description' => 'Pembelian ' . $validated['amount'] . ' Voucher ' . $profile->name,
                        'reference_id' => 'VOUCHER-GEN',
                    ]);
                    $paymentMethod = 'balance';
                } else {
                    // Bayar tunai di kantor / non-saldo
                    $paymentMethod = 'cash';
                }
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
            $prefix = $validated['prefix'] ?? '';
            $len = $validated['length'];

            $code = strtoupper($prefix . $generateString($len, $combination));

            // Ensure unique code
            while(Voucher::where('code', $code)->exists()) {
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
                'reseller_id' => $reseller ? $reseller->id : null,
                'code' => $code,
                'username' => $username,
                'password' => $password,
                'status' => 'available',
                'is_free' => $isFree,
                'payment_method' => $paymentMethod,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Voucher::insert($vouchers);

        // Sync ke RADIUS secara eksplisit karena insert() tidak memicu event
        if (!empty($vouchers)) {
            $createdVouchers = Voucher::whereIn('code', array_column($vouchers, 'code'))->get();
            app(RadiusService::class)->syncVouchers($createdVouchers);
        }

        $label = $isFree ? 'Gratis' : 'Berbayar';
        return redirect()->back()->with('success', $validated['amount'] . " Voucher ({$label}) berhasil di-generate.");
    }

    public function destroy(Voucher $voucher)
    {
        $voucher->delete();
        return redirect()->back()->with('success', 'Voucher berhasil dihapus.');
    }

    public function toggleStatus(Voucher $voucher)
    {
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
        
        $vouchersToDelete = Voucher::whereIn('id', $validated['ids'])->get();
        
        Voucher::whereIn('id', $validated['ids'])->delete();
        
        // Hapus dari RADIUS secara eksplisit
        $radius = app(RadiusService::class);
        foreach ($vouchersToDelete as $v) {
            $radius->removeUser($v->username);
        }
        
        return redirect()->back()->with('success', count($validated['ids']) . ' Voucher berhasil dihapus.');
    }

    public function print(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:vouchers,id',
        ]);
        
        $vouchers = Voucher::with(['profile', 'reseller.customer'])->whereIn('id', $validated['ids'])->get();
        
        return view('vouchers.print', compact('vouchers'));
    }
}
