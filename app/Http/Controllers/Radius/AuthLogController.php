<?php

namespace App\Http\Controllers\Radius;

use App\Http\Controllers\Controller;
use App\Models\Radius\RadPostAuth;
use Illuminate\Http\RedirectResponse;
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
                $query->where(function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                      ->orWhere('reply', 'like', "%{$search}%");
                });
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

    /**
     * Delete a single RADIUS authentication log.
     */
    public function destroy($id): RedirectResponse
    {
        $log = RadPostAuth::find($id);

        if (!$log) {
            return redirect()->back()->with('error', 'Log autentikasi tidak ditemukan.');
        }

        $log->delete();

        return redirect()->back()->with('success', 'Log autentikasi berhasil dihapus.');
    }

    /**
     * Bulk delete or purge RADIUS authentication logs.
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        if ($request->boolean('all')) {
            $query = RadPostAuth::query();
            $mode = $request->input('mode', 'all');

            if ($mode === 'failed') {
                $query->where('reply', '!=', 'Access-Accept');
            } elseif ($mode === '7_days') {
                $query->where('authdate', '<', now()->subDays(7));
            } elseif ($mode === '30_days') {
                $query->where('authdate', '<', now()->subDays(30));
            } elseif ($mode === 'filtered') {
                if ($request->status === 'success') {
                    $query->where('reply', 'Access-Accept');
                } elseif ($request->status === 'failed') {
                    $query->where('reply', '!=', 'Access-Accept');
                }
                if ($request->filled('search')) {
                    $search = $request->search;
                    $query->where(function ($q) use ($search) {
                        $q->where('username', 'like', "%{$search}%")
                          ->orWhere('reply', 'like', "%{$search}%");
                    });
                }
            }

            $count = $query->delete();

            return redirect()->back()->with('success', "Sebanyak {$count} riwayat log autentikasi berhasil dibersihkan.");
        }

        $ids = $request->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            return redirect()->back()->with('error', 'Pilih minimal satu baris log untuk dihapus.');
        }

        $count = RadPostAuth::whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', "Sebanyak {$count} log autentikasi berhasil dihapus.");
    }
}
