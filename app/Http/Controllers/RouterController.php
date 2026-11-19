<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\User;
use App\Models\Area;
use App\Services\NasManagementService;
use App\Services\NasConfigurationService;
use App\Services\RouterConnectionService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RouterController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $query = Router::with([
            'pic:id,name,role',
            'nas',
            'area' => function ($q) {
                $q->withCount(['customers', 'odps']);
            }
        ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('area', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('nas', function ($sq) use ($search) {
                      $sq->where('nasname', 'like', "%{$search}%")
                        ->orWhere('vpn_ip', 'like', "%{$search}%");
                  })
                  ->orWhereHas('pic', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        $routers = $query->latest('id')->paginate(10)->withQueryString();
        
        $users = User::select('id', 'name', 'role')->orderBy('name')->get();
        $areas = Area::select('id', 'name')->withCount(['customers', 'odps'])->orderBy('name')->get();

        $stats = [
            'total_routers' => Router::count(),
            'covered_areas' => Router::whereNotNull('area_id')->distinct('area_id')->count('area_id'),
            'total_areas' => Area::count(),
            'vpn_enabled' => (bool) config('radius.vpn.enabled'),
        ];

        return Inertia::render('Routers/Index', [
            'routers' => $routers,
            'users' => $users,
            'areas' => $areas,
            'stats' => $stats,
            'filters' => $request->only(['search', 'area_id']),
            'vpnEnabled' => (bool) config('radius.vpn.enabled'),
            'serverIp' => config('radius.server_ip') !== '127.0.0.1' ? config('radius.server_ip') : $request->getHost(),
            'vpn' => [
                'enabled' => (bool) config('radius.vpn.enabled'),
                'gateway' => config('radius.vpn.gateway'),
                'endpoints' => config('radius.vpn.endpoints'),
                'backup' => app(\App\Services\VpnAccountService::class)->backupClientConfig(),
            ],
            'clientPool' => config('radius.client_pool'),
            'isolirUrl' => config('radius.isolir_url'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'area_id' => 'nullable',
            'new_area_name' => 'nullable|string|max:255',
            'api_port' => 'nullable|integer|between:1,65535',
            'winbox_port' => 'nullable|integer|between:1,65535',
            'nasname' => config('radius.vpn.enabled') ? 'nullable' : 'required|ip',
            'pic_id' => 'nullable|exists:users,id',
        ]);

        $validated['name'] = trim($validated['name']);
        $validated['api_port'] = !empty($validated['api_port']) ? (int) $validated['api_port'] : 8728;
        $validated['winbox_port'] = !empty($validated['winbox_port']) ? (int) $validated['winbox_port'] : 8291;

        if (!empty($validated['new_area_name'])) {
            $newArea = Area::findOrCreateFlexible(trim($validated['new_area_name']));
            $validated['area_id'] = $newArea->id;
        } elseif (!empty($validated['area_id']) && $validated['area_id'] !== 'new') {
            $validated['area_id'] = Area::where('id', $validated['area_id'])->value('id');
        } else {
            $validated['area_id'] = null;
        }
        unset($validated['new_area_name']);

        $nas = app(NasManagementService::class)->create([
            'nasname' => $validated['nasname'] ?? null,
            'shortname' => Str::substr($validated['name'], 0, 32),
            'type' => 'mikrotik',
            'secret' => Str::random(32),
        ], (bool) config('radius.vpn.enabled'));
        unset($validated['nasname']);
        try {
            Router::create([...$validated, 'nas_id' => $nas->id]);
        } catch (\Throwable $e) {
            app(NasManagementService::class)->delete($nas);
            throw $e;
        }
        $state = app(NasConfigurationService::class)->afterMutation();
        return redirect()->route('routers.index')->with($state['success'] ? 'success' : 'error', 'Router & NAS tersimpan dengan IP '.$nas->nasname.'. '.$state['message']);
    }

    public function update(Request $request, Router $router)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'area_id' => 'nullable',
            'new_area_name' => 'nullable|string|max:255',
            'api_port' => 'nullable|integer|between:1,65535',
            'winbox_port' => 'nullable|integer|between:1,65535',
            'pic_id' => 'nullable|exists:users,id',
        ]);

        $validated['name'] = trim($validated['name']);
        $validated['api_port'] = !empty($validated['api_port']) ? (int) $validated['api_port'] : 8728;
        $validated['winbox_port'] = !empty($validated['winbox_port']) ? (int) $validated['winbox_port'] : 8291;

        if (!empty($validated['new_area_name'])) {
            $newArea = Area::findOrCreateFlexible(trim($validated['new_area_name']));
            $validated['area_id'] = $newArea->id;
        } elseif (!empty($validated['area_id']) && $validated['area_id'] !== 'new') {
            $validated['area_id'] = Area::where('id', $validated['area_id'])->value('id');
        } else {
            $validated['area_id'] = null;
        }
        unset($validated['new_area_name']);

        if ($router->nas) {
            app(NasManagementService::class)->update($router->nas, ['shortname' => Str::substr($validated['name'], 0, 32)]);
        }
        $router->update($validated);

        $state = app(NasConfigurationService::class)->afterMutation();
        return redirect()->route('routers.index')->with($state['success'] ? 'success' : 'error', 'Router diperbarui. '.$state['message']);
    }

    public function destroy(Router $router)
    {
        $nas = $router->nas;
        $router->delete();
        if ($nas && !Router::where('nas_id', $nas->id)->exists()) {
            app(NasManagementService::class)->delete($nas);
        }
        $state = app(NasConfigurationService::class)->afterMutation();
        return redirect()->route('routers.index')->with($state['success'] ? 'success' : 'error', 'Router berhasil dihapus. '.$state['message']);
    }

    public function ping(Request $request, Router $router)
    {
        $refresh = $request->boolean('refresh');
        return response()->json($router->nas
            ? app(RouterConnectionService::class)->probe($router->nas, (int) ($router->api_port ?: 8728), $refresh)
            : ['status' => 'offline', 'message' => 'Router belum memiliki NAS.']);
    }
}
