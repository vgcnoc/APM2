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

        // Optionally, create NAS entry automatically here if needed
        // $nas = \App\Models\Radius\Nas::create([
        //     'nasname' => '0.0.0.0/0',
        //     'shortname' => $router->name,
        //     'type' => 'mikrotik',
        //     'secret' => substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 10),
        // ]);
        // $router->update(['nas_id' => $nas->id]);

        return redirect()->route('routers.index')->with('success', 'Router berhasil ditambahkan.');
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

        return redirect()->route('routers.index')->with('success', 'Router berhasil diupdate.');
    }

    public function destroy(Router $router)
    {
        $router->delete();
        return redirect()->route('routers.index')->with('success', 'Router berhasil dihapus.');
    }
}
