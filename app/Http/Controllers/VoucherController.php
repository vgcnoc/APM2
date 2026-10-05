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
    public function index(Request $request)
    {
        $query = Voucher::with('profile');

        if ($request->filled('profile_id')) {
            $query->where('voucher_profile_id', $request->profile_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $vouchers = $query->latest()->paginate(20)->withQueryString();
        $user = auth()->user();
        if ($user->isAdmin()) {
            $profiles = VoucherProfile::all();
        } else {
            $profiles = $user->voucherProfiles()->get();
        }
        $resellers = \App\Models\Reseller::with('customer')->get();

        return Inertia::render('Vouchers/Index', [
            'vouchers' => $vouchers,
            'profiles' => $profiles,
            'resellers' => $resellers,
            'filters' => $request->only(['search', 'profile_id', 'status']),
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
            $vouchers = Voucher::with('profile', 'reseller.customer')
                ->whereIn('username', $sessions->keys()->all())
                ->get();
        }

        // Map data
        $rows = $vouchers->map(function ($v) use ($sessions) {
            $s = $sessions->get($v->username);
            return [
                'id' => $v->id,
                'code' => $v->code,
                'username' => $v->username,
                'profile' => $v->profile?->name,
                'reseller' => $v->reseller?->customer?->name ?? 'Admin',
                'ip_address' => $s->framedipaddress,
                'mac_address' => $s->callingstationid,
                'nas_ip' => $s->nasipaddress,
                'uptime' => $s->acctsessiontime ?? ($s->acctstarttime ? now()->diffInSeconds($s->acctstarttime) : 0),
                'download' => $s->acctoutputoctets,
                'upload' => $s->acctinputoctets,
                'login_time' => $s->acctstarttime ? $s->acctstarttime->toIso8601String() : null,
                'is_active' => $v->is_active,
            ];
        });

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

        $vouchers = Voucher::with('profile', 'reseller.customer')
            ->whereNotIn('username', $onlineUsernames)
            ->get();

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'voucher_profile_id' => 'required|exists:voucher_profiles,id',
            'amount' => 'required|integer|min:1|max:500',
            'length' => 'required|integer|min:4|max:12',
            'prefix' => 'nullable|string|max:4',
            'type' => 'required|in:up,vc', // up = user & password, vc = code only (user=pass=code)
            'reseller_id' => 'nullable|exists:resellers,id',
        ]);

        $profile = VoucherProfile::find($validated['voucher_profile_id']);
        
        $reseller = null;
        if (!empty($validated['reseller_id'])) {
            $reseller = \App\Models\Reseller::find($validated['reseller_id']);
            $totalPrice = $profile->price * $validated['amount'];
            
            if ($reseller->balance < $totalPrice) {
                return redirect()->back()->withErrors(['reseller_id' => 'Saldo reseller tidak mencukupi (Butuh Rp ' . number_format($totalPrice, 0, ',', '.') . ').'])->withInput();
            }
            
            // Potong saldo reseller
            $reseller->balance -= $totalPrice;
            $reseller->save();
        }
        
        $vouchers = [];
        $now = now();
        
        for ($i = 0; $i < $validated['amount']; $i++) {
            $code = strtoupper($validated['prefix'] . Str::random($validated['length']));
            
            // Ensure unique code
            while(Voucher::where('code', $code)->exists()) {
                $code = strtoupper($validated['prefix'] . Str::random($validated['length']));
            }
            
            if ($validated['type'] === 'vc') {
                $username = $code;
                $password = $code;
            } else {
                $username = $code;
                $password = Str::random($validated['length']);
            }

            $vouchers[] = [
                'voucher_profile_id' => $profile->id,
                'reseller_id' => $reseller ? $reseller->id : null,
                'code' => $code,
                'username' => $username,
                'password' => $password,
                'status' => 'available',
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

        return redirect()->back()->with('success', $validated['amount'] . ' Voucher berhasil di-generate.');
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
            app(\App\Services\RadiusService::class)->disconnectUser($voucher->username);
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
        
        $vouchers = Voucher::with('profile')->whereIn('id', $validated['ids'])->get();
        
        return view('vouchers.print', compact('vouchers'));
    }
}
