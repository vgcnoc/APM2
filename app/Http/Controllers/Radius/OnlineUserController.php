<?php

namespace App\Http\Controllers\Radius;

use App\Http\Controllers\Controller;
use App\Models\Radius\Nas;
use App\Models\Radius\RadAcct;
use App\Models\Router;
use App\Services\RadiusService;
use App\Services\RouterOsApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnlineUserController extends Controller
{
    /**
     * Display a listing of online users.
     */
    public function index(Request $request): Response
    {
        $search = trim($request->input('search', ''));
        $query = RadAcct::online()->fresh();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('framedipaddress', 'like', "%{$search}%")
                  ->orWhere('callingstationid', 'like', "%{$search}%")
                  ->orWhere('nasipaddress', 'like', "%{$search}%");
            });
        }

        if ($request->filled('nas_ip')) {
            $query->where('nasipaddress', $request->nas_ip);
        }

        $stats = [
            'total_online' => RadAcct::online()->fresh()->count(),
            'total_download' => (int) RadAcct::online()->fresh()->sum('acctoutputoctets'),
            'total_upload' => (int) RadAcct::online()->fresh()->sum('acctinputoctets'),
            'active_routers' => RadAcct::online()->fresh()->distinct('nasipaddress')->count('nasipaddress'),
        ];

        $onlineUsers = $query
            ->orderByDesc('acctstarttime')
            ->paginate(20)
            ->withQueryString();

        $nases = Nas::select('id', 'nasname', 'shortname', 'vpn_ip')->get();
        $nasMap = $nases->keyBy(fn ($n) => \App\Support\NasAddress::ip($n->vpn_ip) ?: \App\Support\NasAddress::ip($n->nasname));

        $usernames = $onlineUsers->pluck('username')->unique()->filter()->values()->all();

        // Customer mapping
        $customerMap = collect();
        if (!empty($usernames)) {
            $customerMap = \App\Models\Customer::whereIn('customer_code', $usernames)
                ->orWhereHas('ont', fn ($q) => $q->whereIn('pppoe_user', $usernames)->orWhereIn('hotspot_user', $usernames))
                ->with(['package:id,name', 'area:id,name', 'ont:id,customer_id,pppoe_user,hotspot_user'])
                ->get()
                ->keyBy(function ($c) {
                    return $c->ont?->pppoe_user ?: ($c->ont?->hotspot_user ?: $c->customer_code);
                });
        }

        // Voucher mapping
        $voucherMap = collect();
        if (!empty($usernames)) {
            $voucherMap = \App\Models\Voucher::whereIn('code', $usernames)
                ->orWhereIn('username', $usernames)
                ->with(['profile:id,name'])
                ->get()
                ->keyBy(fn ($v) => $v->code ?: $v->username);
        }

        $onlineUsers->getCollection()->transform(function ($u) use ($nasMap, $customerMap, $voucherMap) {
            $nas = $nasMap->get($u->nasipaddress);
            $u->setAttribute('nas_name', $nas?->shortname ?: $nas?->nasname);
            $u->setAttribute('live_session_time', $u->liveSessionTime());
            
            $customer = $customerMap->get($u->username);
            if ($customer) {
                $u->setAttribute('customer_name', $customer->name);
                $u->setAttribute('customer_id', $customer->id);
                $u->setAttribute('customer_code', $customer->customer_id);
                $u->setAttribute('package_name', $customer->package?->name);
                $u->setAttribute('area_name', $customer->area?->name);
                $u->setAttribute('user_type', 'pppoe');
            } elseif ($voucher = $voucherMap->get($u->username)) {
                $u->setAttribute('voucher_code', $voucher->code);
                $u->setAttribute('profile_name', $voucher->profile?->name);
                $u->setAttribute('user_type', 'voucher');
            } else {
                $u->setAttribute('user_type', 'unknown');
            }
            return $u;
        });

        return Inertia::render('Radius/OnlineUsers/Index', [
            'users' => $onlineUsers,
            'stats' => $stats,
            'nases' => $nases,
            'filters' => $request->only(['search', 'nas_ip']),
        ]);
    }

    /**
     * Disconnect a specific user via CoA / Disconnect-Request, with RouterOS API fallback
     */
    public function disconnect(Request $request, RadiusService $radiusService): RedirectResponse
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'code' => 'nullable|string',
            'nasipaddress' => 'nullable|string',
        ]);

        $username = trim($validated['username']);
        $code = trim($request->input('code', ''));
        $count = $radiusService->disconnect($username);

        if ($count === 0 && !empty($code) && strcasecmp($code, $username) !== 0) {
            $count = $radiusService->disconnect($code);
        }

        // Fallback: Disconnect langsung via RouterOS API jika radclient/CoA tidak merespons
        if ($count === 0) {
            try {
                $client = app(RouterOsApiClient::class);
                $nases = Nas::all();

                if (!empty($validated['nasipaddress'])) {
                    $targetIp = \App\Support\NasAddress::ip($validated['nasipaddress']);
                    $nases = $nases->sortByDesc(fn ($n) => ($n->nasname === $targetIp || $n->vpn_ip === $targetIp) ? 1 : 0);
                }

                $ports = Router::whereNotNull('nas_id')->pluck('api_port', 'nas_id');
                $targets = array_unique(array_filter([$username, $code]));

                foreach ($nases as $nas) {
                    $host = $nas->apiHost();
                    $user = $nas->api_user;
                    $pass = (string) $nas->api_password;
                    $port = (int) ($ports->get($nas->id) ?: 8728);
                    if (!$host || !$user || !$pass) continue;

                    try {
                        $client->connect($host, $port, $user, $pass, 1.5);
                        foreach ($targets as $target) {
                            $pppRows = $client->command('/ppp/active/print', ['?name' => $target, '.proplist' => '.id'])['rows'] ?? [];
                            foreach ($pppRows as $pr) {
                                if (!empty($pr['.id'])) {
                                    $client->command('/ppp/active/remove', ['.id' => $pr['.id']]);
                                    $count++;
                                }
                            }
                            $hsRows = $client->command('/ip/hotspot/active/print', ['?user' => $target, '.proplist' => '.id'])['rows'] ?? [];
                            foreach ($hsRows as $hr) {
                                if (!empty($hr['.id'])) {
                                    $client->command('/ip/hotspot/active/remove', ['.id' => $hr['.id']]);
                                    $count++;
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                    } finally {
                        $client->close();
                    }

                    if ($count > 0) break;
                }
            } catch (\Throwable $e) {}
        }

        if ($count > 0) {
            return redirect()->back()
                ->with('success', "Berhasil memutuskan sesi untuk pengguna {$username}. Sesi telah ditutup.");
        }

        return redirect()->back()
            ->with('error', "Gagal melakukan disconnect. Sesi user {$username} mungkin sudah terputus atau NAS tidak terjangkau.");
    }
}
