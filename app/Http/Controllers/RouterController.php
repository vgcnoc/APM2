<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RouterController extends Controller
{
    public function index(Request $request)
    {
        $query = Router::with(['pic', 'nas']);

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $routers = $query->paginate(10)->withQueryString();
        
        $users = User::select('id', 'name')->get();

        return Inertia::render('Routers/Index', [
            'routers' => $routers,
            'users' => $users,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_port' => 'nullable|integer',
            'winbox_port' => 'nullable|integer',
            'pic_id' => 'nullable|exists:users,id',
        ]);

        $validated['api_port'] = $validated['api_port'] ?? 8728;
        $validated['winbox_port'] = $validated['winbox_port'] ?? 8291;

        $router = Router::create($validated);

        // Auto-create NAS entry
        $nas = \App\Models\Radius\Nas::create([
            'nasname' => '0.0.0.0/0',
            'shortname' => $router->name,
            'type' => 'mikrotik',
            'secret' => substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 10),
        ]);
        $router->update(['nas_id' => $nas->id]);

        return redirect()->route('routers.index')->with('success', 'Router & NAS berhasil ditambahkan.');
    }

    public function update(Request $request, Router $router)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'api_port' => 'nullable|integer',
            'winbox_port' => 'nullable|integer',
            'pic_id' => 'nullable|exists:users,id',
        ]);

        $validated['api_port'] = $validated['api_port'] ?? 8728;
        $validated['winbox_port'] = $validated['winbox_port'] ?? 8291;

        $router->update($validated);
        
        if ($router->nas_id) {
            \App\Models\Radius\Nas::where('id', $router->nas_id)->update(['shortname' => $router->name]);
        }

        return redirect()->route('routers.index')->with('success', 'Router berhasil diupdate.');
    }

    public function destroy(Router $router)
    {
        if ($router->nas_id) {
            \App\Models\Radius\Nas::where('id', $router->nas_id)->delete();
        }
        $router->delete();
        return redirect()->route('routers.index')->with('success', 'Router & NAS berhasil dihapus.');
    }

    public function ping(Router $router)
    {
        $ip = $router->nas ? $router->nas->vpn_ip : null;
        if (!$ip) {
            $ip = $router->nas && $router->nas->nasname !== '0.0.0.0/0' ? $router->nas->nasname : null;
        }

        if (!$ip) {
            return response()->json(['status' => 'offline']);
        }

        $port = $router->api_port ?: 8728;
        
        $fp = @fsockopen($ip, $port, $errno, $errstr, 2);
        if ($fp) {
            fclose($fp);
            return response()->json(['status' => 'online']);
        }

        return response()->json(['status' => 'offline']);
    }
}
