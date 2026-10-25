<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\User;
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
        $query = Router::with(['pic', 'nas', 'area']);

        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('area', function($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        $routers = $query->paginate(10)->withQueryString();
        
        $users = User::select('id', 'name')->get();
        $areas = \App\Models\Area::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Routers/Index', [
            'routers' => $routers,
            'users' => $users,
            'areas' => $areas,
            'filters' => $request->only(['search', 'area_id']),
            'vpnEnabled' => (bool) config('radius.vpn.enabled'),
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

        $validated['api_port'] = $validated['api_port'] ?? 8728;
        $validated['winbox_port'] = $validated['winbox_port'] ?? 8291;

        if (!empty($validated['new_area_name'])) {
            $newArea = \App\Models\Area::firstOrCreate(['name' => trim($validated['new_area_name'])]);
            $validated['area_id'] = $newArea->id;
        } elseif (!empty($validated['area_id']) && $validated['area_id'] !== 'new') {
            $validated['area_id'] = \App\Models\Area::where('id', $validated['area_id'])->value('id');
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

        $validated['api_port'] = $validated['api_port'] ?? 8728;
        $validated['winbox_port'] = $validated['winbox_port'] ?? 8291;

        if (!empty($validated['new_area_name'])) {
            $newArea = \App\Models\Area::firstOrCreate(['name' => trim($validated['new_area_name'])]);
            $validated['area_id'] = $newArea->id;
        } elseif (!empty($validated['area_id']) && $validated['area_id'] !== 'new') {
            $validated['area_id'] = \App\Models\Area::where('id', $validated['area_id'])->value('id');
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
        if ($nas && !Router::where('nas_id', $nas->id)->exists()) app(NasManagementService::class)->delete($nas);
        $state = app(NasConfigurationService::class)->afterMutation();
        return redirect()->route('routers.index')->with($state['success'] ? 'success' : 'error', 'Router dihapus. '.$state['message']);
    }

    public function ping(Router $router)
    {
        return response()->json($router->nas
            ? app(RouterConnectionService::class)->probe($router->nas, (int) ($router->api_port ?: 8728))
            : ['status' => 'offline', 'message' => 'Router belum memiliki NAS.']);
    }
}
