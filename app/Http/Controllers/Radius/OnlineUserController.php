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
        $query = RadAcct::online()->fresh();

        $onlineUsers = $query
            ->when($request->search, fn ($q, $s) =>
                $q->where(fn ($q) => $q->where('username', 'like', "%{$s}%")
                  ->orWhere('framedipaddress', 'like', "%{$s}%")
                  ->orWhere('callingstationid', 'like', "%{$s}%")
                  ->orWhere('nasipaddress', 'like', "%{$s}%"))
            )
            ->orderByDesc('acctstarttime')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Radius/OnlineUsers/Index', [
            'users' => $onlineUsers,
            'filters' => $request->only(['search']),
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
                }
            } catch (\Throwable $e) {}
        }

        if ($count > 0) {
            return redirect()->back()
                ->with('success', "Berhasil mengirim perintah disconnect untuk user {$username}.");
        }

        return redirect()->back()
            ->with('error', "Gagal melakukan disconnect. Sesi user {$username} mungkin sudah terputus atau NAS tidak terjangkau.");
    }
}
