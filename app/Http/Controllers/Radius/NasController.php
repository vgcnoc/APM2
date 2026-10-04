<?php

namespace App\Http\Controllers\Radius;

use App\Http\Controllers\Controller;
use App\Models\Radius\Nas;
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

        return Inertia::render('Radius/Nas/Index', [
            'nas' => $nas,
            'filters' => $request->only(['search']),
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

        Nas::create($validated);

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

        return redirect()->route('radius.nas.index')
            ->with('success', 'Data NAS (Router) berhasil dihapus.');
    }
}
