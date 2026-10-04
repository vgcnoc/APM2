<?php

namespace App\Http\Controllers\Radius;

use App\Http\Controllers\Controller;
use App\Models\Radius\RadPostAuth;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthLogController extends Controller
{
    /**
     * Display a listing of RADIUS authentication logs.
     */
    public function index(Request $request): Response
    {
        $logs = RadPostAuth::when($request->search, function ($query, $search) {
                $query->where('username', 'like', "%{$search}%")
                      ->orWhere('reply', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'success') {
                    $query->where('reply', 'Access-Accept');
                } elseif ($status === 'failed') {
                    $query->where('reply', '!=', 'Access-Accept');
                }
            })
            ->orderByDesc('authdate')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Radius/AuthLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'status']),
        ]);
    }
}
