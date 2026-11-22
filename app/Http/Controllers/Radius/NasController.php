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
        $nas = Nas::forCurrentCompany()
            ->when($request->search, fn ($q, $s) => $q->where(fn ($q) => $q
                ->where('nasname', 'like', "%{$s}%")->orWhere('shortname', 'like', "%{$s}%")
                ->orWhere('vpn_ip', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%")))
            ->orderByDesc('id')->paginate(15)->withQueryString();
        $ports = Router::withoutGlobalScopes()->whereIn('nas_id', $nas->pluck('id'))->pluck('api_port', 'nas_id');
        $nas->getCollection()->each(fn ($n) => $n->setAttribute('api_port', (int) (($ports[$n->id] ?? null) ?: 8728)));

        $stats = [
            'total_nas' => Nas::forCurrentCompany()->count(),
            'vpn_nas' => Nas::forCurrentCompany()->whereNotNull('vpn_ip')->count(),
            'direct_nas' => Nas::forCurrentCompany()->whereNull('vpn_ip')->count(),
            'radius_enabled' => (bool) config('radius.enabled'),
        ];

        // GET is read-only: no generation, synchronization, or service restarts.
        return Inertia::render('Radius/Nas/Index', [
            'nas' => $nas,
            'stats' => $stats,
            'filters' => $request->only('search'),
            'serverIp' => config('radius.server_ip') !== '127.0.0.1' ? config('radius.server_ip') : $request->getHost(),
            'vpn' => [
                'enabled' => (bool) config('radius.vpn.enabled'),
                'gateway' => config('radius.vpn.gateway'),
                'endpoints' => config('radius.vpn.endpoints'),
                'backup' => app(\App\Services\VpnAccountService::class)->backupClientConfig(),
            ],
            'clientPool' => config('radius.client_pool'),
            'isolirUrl' => config('radius.isolir_url'),
            'configuration' => app(NasConfigurationService::class)->status(),
            'createdNasId' => session('created_nas_id'),
        ]);
    }

    private function authorizeNas(Nas $nas): void
    {
        if (!Nas::forCurrentCompany()->where('nas.id', $nas->id)->exists()) {
            abort(403, 'Anda tidak memiliki hak akses ke router / NAS ini.');
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'use_vpn' => 'sometimes|boolean',
            'nasname' => 'nullable|string|max:128',
            'shortname' => 'nullable|string|max:32',
            'type' => 'nullable|string|max:30',
            'ports' => 'nullable|integer|between:1,65535',
            'coa_port' => 'nullable|integer|between:1,65535',
            'secret' => ['required', 'string', 'max:60', 'regex:/^[\x21-\x7E]+$/'],
            'server' => 'nullable|string|max:64',
            'community' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:200',
        ]);
    }

    public function store(Request $request, NasManagementService $manager)
    {
        $nas = $manager->create($this->validated($request), $request->boolean('use_vpn', (bool) config('radius.vpn.enabled')));
        $state = app(NasConfigurationService::class)->afterMutation();

        // Buat rekaman Router di tabel routers agar sinkron dengan Data Router
        $companyId = $nas->company_id ?: 1;
        Router::withoutGlobalScopes()->firstOrCreate(
            ['nas_id' => $nas->id],
            [
                'company_id' => $companyId,
                'name' => $nas->shortname ?: $nas->nasname,
                'api_port' => 8728,
                'winbox_port' => 8291,
            ]
        );

        return redirect()->route('radius.nas.index')->with('created_nas_id', $nas->id)
            ->with($state['success'] ? 'success' : 'error', 'NAS tersimpan dengan IP '.$nas->nasname.'. '.$state['message']);
    }

    public function update(Request $request, Nas $nas, NasManagementService $manager)
    {
        $this->authorizeNas($nas);
        $manager->update($nas, $this->validated($request));
        $state = app(NasConfigurationService::class)->afterMutation();
        return redirect()->route('radius.nas.index')->with($state['success'] ? 'success' : 'error', 'NAS diperbarui. '.$state['message']);
    }

    public function destroy(Nas $nas, NasManagementService $manager)
    {
        $this->authorizeNas($nas);
        if (Router::withoutGlobalScopes()->where('nas_id', $nas->id)->exists()) {
            return back()->with('error', 'NAS masih terhubung ke Data Router. Hapus melalui menu Data Router agar relasi jaringan tidak rusak.');
        }
        $manager->delete($nas);
        $state = app(NasConfigurationService::class)->afterMutation();
        return redirect()->route('radius.nas.index')->with($state['success'] ? 'success' : 'error', 'NAS dihapus. '.$state['message']);
    }

    public function regenerate(Nas $nas, NasManagementService $manager)
    {
        $this->authorizeNas($nas);
        $manager->regenerate($nas);
        $state = app(NasConfigurationService::class)->afterMutation();
        return redirect()->route('radius.nas.index')->with($state['success'] ? 'success' : 'error', 'Password VPN & API berhasil digenerate ulang. Paste script baru pada router. '.$state['message']);
    }

    public function apply(NasConfigurationService $configuration)
    {
        $state = $configuration->apply();
        return back()->with($state['success'] ? 'success' : 'error', $state['message']);
    }

    public function ping(Request $request, Nas $nas, RouterConnectionService $connection)
    {
        $this->authorizeNas($nas);
        $refresh = $request->boolean('refresh');
        return response()->json($connection->probe(
            $nas,
            (int) (Router::withoutGlobalScopes()->where('nas_id', $nas->id)->value('api_port') ?: 8728),
            $refresh
        ));
    }
}
