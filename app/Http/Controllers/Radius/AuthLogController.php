<?php

namespace App\Http\Controllers\Radius;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Ont;
use App\Models\Radius\RadCheck;
use App\Models\Radius\RadPostAuth;
use App\Models\Voucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuthLogController extends Controller
{
    /**
     * Display a listing of RADIUS authentication logs.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $period = $request->input('period');
        $userTypeFilter = $request->input('user_type');
        $perPage = (int) $request->input('per_page', 15);

        if (!in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        // Hitung statistik ringkas secara efisien dalam 1 query
        $statsRaw = RadPostAuth::selectRaw("
            COUNT(*) as total_logs,
            SUM(CASE WHEN reply = 'Access-Accept' THEN 1 ELSE 0 END) as total_success,
            SUM(CASE WHEN reply != 'Access-Accept' THEN 1 ELSE 0 END) as total_failed,
            SUM(CASE WHEN DATE(authdate) = CURRENT_DATE() THEN 1 ELSE 0 END) as today_logs
        ")->first();

        $totalLogs = (int) ($statsRaw->total_logs ?? 0);
        $totalSuccess = (int) ($statsRaw->total_success ?? 0);
        $totalFailed = (int) ($statsRaw->total_failed ?? 0);
        $todayLogs = (int) ($statsRaw->today_logs ?? 0);
        $successRate = $totalLogs > 0 ? round(($totalSuccess / $totalLogs) * 100, 1) : 0;
        $failRate = $totalLogs > 0 ? round(($totalFailed / $totalLogs) * 100, 1) : 0;

        $stats = [
            'total_logs' => $totalLogs,
            'total_success' => $totalSuccess,
            'total_failed' => $totalFailed,
            'today_logs' => $todayLogs,
            'success_rate' => $successRate,
            'fail_rate' => $failRate,
        ];

        // Query tabel radpostauth
        $query = RadPostAuth::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('reply', 'like', "%{$search}%")
                  ->orWhere('class', 'like', "%{$search}%");
            });
        }

        if ($status === 'success') {
            $query->where('reply', 'Access-Accept');
        } elseif ($status === 'failed') {
            $query->where('reply', '!=', 'Access-Accept');
        }

        if ($period === 'today') {
            $query->whereDate('authdate', today());
        } elseif ($period === 'yesterday') {
            $query->whereDate('authdate', today()->subDay());
        } elseif ($period === '7_days') {
            $query->where('authdate', '>=', now()->subDays(7));
        } elseif ($period === '30_days') {
            $query->where('authdate', '>=', now()->subDays(30));
        }

        $logs = $query->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        // Resolusi data Customer, Voucher, dan RadCheck untuk item di halaman saat ini
        $items = collect($logs->items());
        $usernames = $items->pluck('username')->filter()->unique()->values();

        $customers = collect();
        if ($usernames->isNotEmpty()) {
            // 1. Cari via ONT (pppoe_user atau hotspot_user)
            $onts = Ont::where(function ($q) use ($usernames) {
                    $q->whereIn('pppoe_user', $usernames)
                      ->orWhereIn('hotspot_user', $usernames);
                })
                ->whereNotNull('customer_id')
                ->with(['customer.package:id,name,speed_mbps,price', 'customer.area:id,name'])
                ->get();

            foreach ($onts as $ont) {
                if ($ont->customer) {
                    if ($ont->pppoe_user) {
                        $customers->put($ont->pppoe_user, $ont->customer);
                        $customers->put(strtolower($ont->pppoe_user), $ont->customer);
                    }
                    if ($ont->hotspot_user) {
                        $customers->put($ont->hotspot_user, $ont->customer);
                        $customers->put(strtolower($ont->hotspot_user), $ont->customer);
                    }
                }
            }

            // 2. Cari via Customer langsung (customer_code, email, atau phone)
            $directCustomers = Customer::whereIn('customer_code', $usernames)
                ->orWhereIn('email', $usernames)
                ->orWhereIn('phone', $usernames)
                ->with(['package:id,name,speed_mbps,price', 'area:id,name'])
                ->get(['id', 'name', 'customer_code', 'email', 'package_id', 'area_id', 'status', 'phone']);

            foreach ($directCustomers as $cust) {
                if ($cust->customer_code) {
                    $customers->put($cust->customer_code, $cust);
                    $customers->put(strtolower($cust->customer_code), $cust);
                }
                if ($cust->email) {
                    $customers->put($cust->email, $cust);
                    $customers->put(strtolower($cust->email), $cust);
                }
                if ($cust->phone) {
                    $customers->put($cust->phone, $cust);
                }
            }
        }

        // Resolusi Voucher
        $voucherMap = collect();
        if ($usernames->isNotEmpty()) {
            $vouchers = Voucher::where(function ($q) use ($usernames) {
                    $q->whereIn('code', $usernames)
                      ->orWhereIn('username', $usernames);
                })
                ->with('profile:id,name')
                ->get(['id', 'code', 'username', 'voucher_profile_id', 'status', 'used_at']);

            foreach ($vouchers as $voucher) {
                if ($voucher->code) {
                    $voucherMap->put($voucher->code, $voucher);
                    $voucherMap->put(strtolower($voucher->code), $voucher);
                }
                if ($voucher->username) {
                    $voucherMap->put($voucher->username, $voucher);
                    $voucherMap->put(strtolower($voucher->username), $voucher);
                }
            }
        }

        // Resolusi RadCheck
        $radChecks = collect();
        if ($usernames->isNotEmpty()) {
            $radChecks = RadCheck::whereIn('username', $usernames)
                ->get(['username', 'attribute', 'value'])
                ->groupBy(fn ($rc) => strtolower($rc->username));
        }

        $transformedItems = $items->map(function ($item) use ($customers, $voucherMap, $radChecks) {
            $u = (string) $item->username;
            $uLower = strtolower($u);

            $customer = $customers->get($u) ?: $customers->get($uLower);
            $voucher = $voucherMap->get($u) ?: $voucherMap->get($uLower);
            $radCheck = $radChecks->get($uLower);

            $userType = 'unknown';
            if ($customer) {
                $userType = 'customer';
            } elseif ($voucher) {
                $userType = 'voucher';
            }

            // Diagnosa status autentikasi untuk memudahkan NOC / Admin
            $diagnosis = 'Autentikasi Berhasil (Login Sukses)';
            $diagnosisType = 'success';

            if ($item->reply !== 'Access-Accept') {
                $diagnosisType = 'danger';
                if ($customer) {
                    $custStatus = strtolower((string) $customer->status);
                    if (in_array($custStatus, ['suspended', 'isolir', 'isolated', 'inactive', 'off'])) {
                        $diagnosis = 'Pelanggan Di-suspend / Isolir';
                        $diagnosisType = 'warning';
                    } else {
                        $diagnosis = 'Password PPPoE Salah / Mismatch';
                    }
                } elseif ($voucher) {
                    $vStatus = strtolower((string) $voucher->status);
                    if ($voucher->used_at || $vStatus === 'used' || $vStatus === 'expired') {
                        $diagnosis = 'Voucher Kadaluarsa / Habis Kuota';
                        $diagnosisType = 'warning';
                    } else {
                        $diagnosis = 'Password Voucher Tidak Cocok';
                    }
                } elseif ($radCheck && $radCheck->isNotEmpty()) {
                    $diagnosis = 'Password Salah (Akun Terdaftar di RADIUS)';
                } else {
                    $diagnosis = 'Username Tidak Terdaftar';
                    $diagnosisType = 'info';
                }
            }

            return [
                'id' => $item->id,
                'username' => $item->username,
                'pass' => $item->pass,
                'reply' => $item->reply,
                'authdate' => $item->authdate ? $item->authdate->toIso8601String() : null,
                'class' => $item->class,
                'user_type' => $userType,
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'customer_id' => $customer->customer_code,
                    'package_name' => $customer->package->name ?? '-',
                    'speed' => $customer->package->speed_mbps ? $customer->package->speed_mbps . ' Mbps' : '-',
                    'area_name' => $customer->area->name ?? '-',
                    'status' => $customer->status,
                    'phone' => $customer->phone,
                ] : null,
                'voucher' => $voucher ? [
                    'id' => $voucher->id,
                    'code' => $voucher->code ?: $voucher->username,
                    'package_name' => $voucher->profile->name ?? 'Hotspot',
                    'status' => $voucher->status,
                    'is_used' => (bool) ($voucher->used_at || $voucher->status === 'used'),
                ] : null,
                'has_radcheck' => (bool) ($radCheck && $radCheck->isNotEmpty()),
                'diagnosis' => $diagnosis,
                'diagnosis_type' => $diagnosisType,
            ];
        });

        // Filter tipe user jika user memilih filter khusus di frontend
        if ($userTypeFilter && in_array($userTypeFilter, ['customer', 'voucher', 'unknown'], true)) {
            $transformedItems = $transformedItems->filter(fn ($item) => $item['user_type'] === $userTypeFilter)->values();
        }

        $logs->setCollection($transformedItems);

        return Inertia::render('Radius/AuthLogs/Index', [
            'logs' => $logs,
            'filters' => [
                'search' => $search,
                'status' => $status ?? '',
                'period' => $period ?? '',
                'user_type' => $userTypeFilter ?? '',
                'per_page' => $perPage,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Export RADIUS authentication logs as CSV stream.
     */
    public function export(Request $request): StreamedResponse
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $period = $request->input('period');

        $query = RadPostAuth::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('reply', 'like', "%{$search}%")
                  ->orWhere('class', 'like', "%{$search}%");
            });
        }

        if ($status === 'success') {
            $query->where('reply', 'Access-Accept');
        } elseif ($status === 'failed') {
            $query->where('reply', '!=', 'Access-Accept');
        }

        if ($period === 'today') {
            $query->whereDate('authdate', today());
        } elseif ($period === 'yesterday') {
            $query->whereDate('authdate', today()->subDay());
        } elseif ($period === '7_days') {
            $query->where('authdate', '>=', now()->subDays(7));
        } elseif ($period === '30_days') {
            $query->where('authdate', '>=', now()->subDays(30));
        }

        $filename = 'radius_auth_logs_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM agar terbaca sempurna di Microsoft Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'ID',
                'Tanggal & Waktu',
                'Username',
                'Password Diajukan',
                'Status',
                'Balasan (Reply)',
                'Class',
            ]);

            $query->orderByDesc('id')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->id,
                        $row->authdate ? $row->authdate->format('Y-m-d H:i:s') : '-',
                        $row->username,
                        $row->pass ?: '-',
                        $row->reply === 'Access-Accept' ? 'Sukses' : 'Gagal',
                        $row->reply,
                        $row->class ?: '-',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
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
            } elseif ($mode === 'success') {
                $query->where('reply', 'Access-Accept');
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
                if ($request->filled('period')) {
                    $period = $request->period;
                    if ($period === 'today') {
                        $query->whereDate('authdate', today());
                    } elseif ($period === 'yesterday') {
                        $query->whereDate('authdate', today()->subDay());
                    } elseif ($period === '7_days') {
                        $query->where('authdate', '>=', now()->subDays(7));
                    } elseif ($period === '30_days') {
                        $query->where('authdate', '>=', now()->subDays(30));
                    }
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
