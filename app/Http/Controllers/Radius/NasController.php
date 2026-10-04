<?php

namespace App\Http\Controllers\Radius;

use App\Http\Controllers\Controller;
use App\Models\Radius\Nas;
use App\Services\VpnAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NasController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Nas::query();

        $nas = $query
            ->when($request->search, fn ($q, $s) =>
                $q->where('nasname', 'like', "%{$s}%")
                  ->orWhere('shortname', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%"))
            ->orderBy('nasname')
            ->paginate(15)
            ->withQueryString();

        // Router lama (sebelum fitur VPN) otomatis dibuatkan akun VPN/API
        if (config('radius.vpn.enabled')) {
            $vpn = app(VpnAccountService::class);
            $nas->getCollection()
                ->filter(fn (Nas $n) => !$n->vpn_user || !$n->vpn_ip || !$n->api_user)
                ->each(fn (Nas $n) => $vpn->ensureAccount($n));
        }

        return Inertia::render('Radius/Nas/Index', [
            'nas' => $nas,
            'filters' => $request->only(['search']),
            'serverIp' => config('radius.server_ip') !== '127.0.0.1' ? config('radius.server_ip') : $request->getHost(),
            'vpn' => [
                'enabled' => (bool) config('radius.vpn.enabled'),
                'gateway' => config('radius.vpn.gateway'),
                'endpoints' => config('radius.vpn.endpoints'),
            ],
            'clientPool' => config('radius.client_pool'),
            'isolirUrl' => config('radius.isolir_url'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nasname' => 'required|string|max:128|unique:radius.nas,nasname',
            'shortname' => 'nullable|string|max:32',
            'type' => 'nullable|string|max:30',
            'ports' => 'nullable|integer',
            'secret' => 'required|string|max:60',
            'server' => 'nullable|string|max:64',
            'community' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:200',
        ]);

        $nas = Nas::create($validated);

        if (config('radius.vpn.enabled')) {
            app(VpnAccountService::class)->ensureAccount($nas);
        }

        return redirect()->route('radius.nas.index')
            ->with('success', 'Data NAS (Router) berhasil ditambahkan.');
    }

    public function update(Request $request, Nas $nas): RedirectResponse
    {
        $validated = $request->validate([
            'nasname' => 'required|string|max:128|unique:radius.nas,nasname,' . $nas->id,
            'shortname' => 'nullable|string|max:32',
            'type' => 'nullable|string|max:30',
            'ports' => 'nullable|integer',
            'secret' => 'required|string|max:60',
            'server' => 'nullable|string|max:64',
            'community' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:200',
        ]);

        $nas->update($validated);

        return redirect()->route('radius.nas.index')
            ->with('success', 'Data NAS (Router) berhasil diperbarui.');
    }

    public function destroy(Nas $nas): RedirectResponse
    {
        $nas->delete();

        if (config('radius.vpn.enabled')) {
            app(VpnAccountService::class)->sync();
        }

        return redirect()->route('radius.nas.index')
            ->with('success', 'Data NAS (Router) berhasil dihapus.');
    }

    /**
     * Generate ulang password VPN & API (script lama di router harus diganti).
     */
    public function regenerate(Nas $nas, VpnAccountService $vpn): RedirectResponse
    {
        $vpn->ensureAccount($nas);
        $vpn->regenerate($nas);

        return redirect()->route('radius.nas.index')
            ->with('success', 'Kredensial VPN & API router berhasil di-generate ulang. Paste ulang script ke Mikrotik.');
    }

    public function ping(Nas $nas)
    {
        $ip = $nas->vpn_ip;
        if (!$ip && $nas->nasname !== '0.0.0.0/0') {
            $ip = $nas->nasname;
        }

        if (!$ip) {
            return response()->json(['status' => 'offline']);
        }

        $router = \App\Models\Router::where('nas_id', $nas->id)->first();
        $port = $router ? ($router->api_port ?: 8728) : 8728;
        
        $fp = @fsockopen($ip, $port, $errno, $errstr, 2);
        if ($fp) {
            fclose($fp);
            return response()->json(['status' => 'online']);
        }

        return response()->json(['status' => 'offline']);
    }
}
