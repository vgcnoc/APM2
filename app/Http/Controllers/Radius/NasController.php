<?php

namespace App\Http\Controllers\Radius;

use App\Http\Controllers\Controller;
use App\Models\Radius\Nas;
use App\Models\Router;
use App\Services\NasConfigurationService;
use App\Services\NasManagementService;
use App\Services\RouterConnectionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NasController extends Controller
{
    public function index(Request $request)
    {
        $nas = Nas::when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
            ->where('nasname', 'like', "%{$s}%")->orWhere('shortname', 'like', "%{$s}%")
            ->orWhere('vpn_ip', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%")))
            ->orderByDesc('id')->paginate(15)->withQueryString();
        $ports = Router::whereIn('nas_id', $nas->pluck('id'))->pluck('api_port', 'nas_id');
        $nas->getCollection()->each(fn ($n) => $n->setAttribute('api_port', (int) (($ports[$n->id] ?? null) ?: 8728)));
        // GET is read-only: no generation, synchronization, or service restarts.
        return Inertia::render('Radius/Nas/Index', [
            'nas' => $nas, 'filters' => $request->only('search'),
            'serverIp' => config('radius.server_ip') !== '127.0.0.1' ? config('radius.server_ip') : $request->getHost(),
            'vpn' => ['enabled' => (bool) config('radius.vpn.enabled'), 'gateway' => config('radius.vpn.gateway'), 'endpoints' => config('radius.vpn.endpoints')],
            'clientPool' => config('radius.client_pool'), 'isolirUrl' => config('radius.isolir_url'),
            'configuration' => app(NasConfigurationService::class)->status(),
            'createdNasId' => session('created_nas_id'),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'use_vpn' => 'sometimes|boolean', 'nasname' => 'nullable|string|max:128',
            'shortname' => 'nullable|string|max:32', 'type' => 'nullable|string|max:30',
            'ports' => 'nullable|integer|between:1,65535', 'coa_port' => 'nullable|integer|between:1,65535',
            'secret' => ['required', 'string', 'max:60', 'regex:/^[\x21-\x7E]+$/'],
            'server' => 'nullable|string|max:64', 'community' => 'nullable|string|max:50', 'description' => 'nullable|string|max:200',
        ]);
    }

    public function store(Request $request, NasManagementService $manager)
    {
        $nas = $manager->create($this->validated($request), $request->boolean('use_vpn', (bool) config('radius.vpn.enabled')));
        $state = app(NasConfigurationService::class)->afterMutation();
        return redirect()->route('radius.nas.index')->with('created_nas_id', $nas->id)
            ->with($state['success'] ? 'success' : 'error', 'NAS tersimpan dengan IP '.$nas->nasname.'. '.$state['message']);
    }

    public function update(Request $request, Nas $nas, NasManagementService $manager)
    {
        $manager->update($nas, $this->validated($request));
        $state = app(NasConfigurationService::class)->afterMutation();
        return redirect()->route('radius.nas.index')->with($state['success'] ? 'success' : 'error', 'NAS diperbarui. '.$state['message']);
    }

    public function destroy(Nas $nas, NasManagementService $manager)
    {
        if (Router::where('nas_id', $nas->id)->exists()) return back()->with('error', 'NAS masih terhubung ke Data Router. Hapus melalui Data Router agar relasinya tidak rusak.');
        $manager->delete($nas);
        $state = app(NasConfigurationService::class)->afterMutation();
        return redirect()->route('radius.nas.index')->with($state['success'] ? 'success' : 'error', 'NAS dihapus. '.$state['message']);
    }

    public function regenerate(Nas $nas, NasManagementService $manager)
    {
        $manager->regenerate($nas);
        $state = app(NasConfigurationService::class)->afterMutation();
        return redirect()->route('radius.nas.index')->with($state['success'] ? 'success' : 'error', 'Password VPN/API diganti sesuai permintaan. Paste ulang script pada router. '.$state['message']);
    }

    public function apply(NasConfigurationService $configuration)
    {
        $state = $configuration->apply();
        return back()->with($state['success'] ? 'success' : 'error', $state['message']);
    }

    public function ping(Nas $nas, RouterConnectionService $connection)
    {
        return response()->json($connection->probe($nas, (int) (Router::where('nas_id', $nas->id)->value('api_port') ?: 8728)));
    }
}
