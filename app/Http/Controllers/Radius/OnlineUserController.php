<?php

namespace App\Http\Controllers\Radius;

use App\Http\Controllers\Controller;
use App\Models\Radius\RadAcct;
use App\Services\RadiusService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class OnlineUserController extends Controller
{
    /**
     * Display a listing of online users.
     */
    public function index(Request $request): Response
    {
        $query = RadAcct::online(); // Scope online di RadAcct = acctstoptime is null

        $onlineUsers = $query
            ->when($request->search, fn ($q, $s) =>
                $q->where('username', 'like', "%{$s}%")
                  ->orWhere('framedipaddress', 'like', "%{$s}%")
                  ->orWhere('callingstationid', 'like', "%{$s}%")
                  ->orWhere('nasipaddress', 'like', "%{$s}%")
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
     * Disconnect a specific user via CoA / Disconnect-Request
     */
    public function disconnect(Request $request, RadiusService $radiusService): RedirectResponse
    {
        $validated = $request->validate([
            'username' => 'required|string',
        ]);

        $count = $radiusService->disconnect($validated['username']);

        if ($count > 0) {
            return redirect()->back()
                ->with('success', "Berhasil mengirim perintah disconnect untuk user {$validated['username']}.");
        }

        return redirect()->back()
            ->with('error', "Gagal melakukan disconnect. Pastikan NAS terhubung dan CoA port dikonfigurasi dengan benar.");
    }
}
