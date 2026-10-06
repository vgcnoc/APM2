<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function branding()
    {
        $logo = Setting::get('app_logo');
        return Inertia::render('Settings/Branding', [
            'current_logo' => $logo ? asset('storage/' . $logo) : null,
            'current_app_name' => Setting::get('app_name', ''),
        ]);
    }

    public function billing()
    {
        return Inertia::render('Settings/Billing', [
            'billing_type' => Setting::get('billing_type', 'prabayar'),
            'prorata_formula' => Setting::get('prorata_formula', 'exact_days'),
            'invoice_issue_date' => Setting::get('invoice_issue_date', '1'),
            'isolate_days' => Setting::get('isolate_days', '3'),
            'isolate_time' => Setting::get('isolate_time', '00:00'),
            'payment_banks' => json_decode(Setting::get('payment_banks', '[]'), true),
            'pg_provider' => Setting::get('pg_provider', 'none'),
            'pg_environment' => Setting::get('pg_environment', 'sandbox'),
            'pg_merchant_id' => Setting::get('pg_merchant_id', ''),
            'pg_api_key' => Setting::get('pg_api_key', ''),
            'pg_private_key' => Setting::get('pg_private_key', ''),
            'pg_callback_token' => Setting::get('pg_callback_token', ''),
            'tax_ppn' => Setting::get('tax_ppn', '0'),
            'tax_bhp' => Setting::get('tax_bhp', '0'),
            'tax_uso' => Setting::get('tax_uso', '0'),
        ]);
    }

    public function updateBilling(Request $request)
    {
        $request->validate([
            'billing_type' => 'required|in:prabayar,pascabayar,prorata',
            'prorata_formula' => 'nullable|in:exact_days,fixed_30,mid_month',
            'invoice_issue_date' => 'required|integer|min:1|max:28',
            'isolate_days' => 'required|integer|min:0',
            'isolate_time' => 'required|date_format:H:i',
            'payment_banks' => 'nullable|array',
            'payment_banks.*.bank_name' => 'required|string',
            'payment_banks.*.account_name' => 'required|string',
            'payment_banks.*.account_number' => 'required|string',
            'pg_provider' => 'nullable|string',
            'pg_environment' => 'nullable|string',
            'pg_merchant_id' => 'nullable|string',
            'pg_api_key' => 'nullable|string',
            'pg_private_key' => 'nullable|string',
            'pg_callback_token' => 'nullable|string',
            'tax_ppn' => 'nullable|numeric|min:0|max:100',
            'tax_bhp' => 'nullable|numeric|min:0|max:100',
            'tax_uso' => 'nullable|numeric|min:0|max:100',
        ]);

        Setting::set('billing_type', $request->billing_type);
        if ($request->has('prorata_formula')) {
            Setting::set('prorata_formula', $request->prorata_formula);
        }
        Setting::set('invoice_issue_date', $request->invoice_issue_date);
        Setting::set('isolate_days', $request->isolate_days);
        Setting::set('isolate_time', $request->isolate_time);
        
        Setting::set('tax_ppn', $request->tax_ppn ?? 0);
        Setting::set('tax_bhp', $request->tax_bhp ?? 0);
        Setting::set('tax_uso', $request->tax_uso ?? 0);

        Setting::set('payment_banks', json_encode($request->payment_banks ?? []));
        Setting::set('pg_provider', $request->pg_provider ?? 'none');
        Setting::set('pg_environment', $request->pg_environment ?? 'sandbox');
        Setting::set('pg_merchant_id', $request->pg_merchant_id ?? '');
        Setting::set('pg_api_key', $request->pg_api_key ?? '');
        Setting::set('pg_private_key', $request->pg_private_key ?? '');
        Setting::set('pg_callback_token', $request->pg_callback_token ?? '');

        return redirect()->back()->with('success', 'Pengaturan billing berhasil diperbarui.');
    }

    public function updateBranding(Request $request)
    {
        \Log::info('updateBranding payload:', $request->all());
        \Log::info('hasFile app_logo:', [$request->hasFile('app_logo')]);
        
        $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_logo' => 'nullable|image|max:2048',
            'remove_logo' => 'nullable|boolean'
        ]);

        if ($request->has('app_name')) {
            Setting::set('app_name', $request->app_name ?? '');
        }

        if ($request->boolean('remove_logo')) {
            $oldLogo = Setting::get('app_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            Setting::set('app_logo', null);
        } elseif ($request->hasFile('app_logo')) {
            $oldLogo = Setting::get('app_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            
            $path = $request->file('app_logo')->store('logos', 'public');
            Setting::set('app_logo', $path);
        }

        if ($request->ajax() || $request->wantsJson()) {
            $logo = Setting::get('app_logo');
            return response()->json([
                'success' => true,
                'message' => 'Branding berhasil diperbarui.',
                'app_logo' => $logo ? asset('storage/' . $logo) : null,
            ]);
        }

        return redirect()->back()->with('success', 'Branding berhasil diperbarui.');
    }

    public function apiTest(Request $request)
    {
        $settings = json_decode(file_exists(storage_path('app/settings.json')) ? file_get_contents(storage_path('app/settings.json')) : '{}', true);
        if (empty($settings['app_lk_url'])) {
            return response()->json(['status' => 'error', 'message' => 'URL App-LK belum dikonfigurasi.']);
        }
        try {
            $response = \Illuminate\Support\Facades\Http::withToken($settings['app_lk_token'] ?? '')
                ->timeout(15)
                ->get(rtrim($settings['app_lk_url'], '/') . '/api/ping');
            if ($response->successful()) {
                return response()->json(['status' => 'success', 'message' => 'Koneksi berhasil! app-LK merespons dengan baik.']);
            }
            return response()->json(['status' => 'error', 'message' => 'Koneksi gagal. HTTP Status: ' . $response->status()]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function apiSync(Request $request)
    {
        $settings = json_decode(file_exists(storage_path('app/settings.json')) ? file_get_contents(storage_path('app/settings.json')) : '{}', true);
        if (empty($settings['app_lk_url'])) {
            return response()->json(['status' => 'error', 'message' => 'URL App-LK belum dikonfigurasi.']);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withToken($settings['app_lk_token'] ?? '')
                ->timeout(15)
                ->get(rtrim($settings['app_lk_url'], '/') . '/api/customers');
                
            if ($response->successful()) {
                $customers = $response->json();
                
                if (isset($customers['data']) && is_array($customers['data'])) {
                    $customers = $customers['data'];
                }

                if (!is_array($customers)) {
                    return response()->json(['status' => 'error', 'message' => 'Format balasan dari app-LK tidak valid.']);
                }

                $syncedCount = 0;
                foreach ($customers as $cust) {
                    if (empty($cust['name']) || empty($cust['phone'])) continue;

                    $existing = \App\Models\Customer::where('phone', $cust['phone'])->first();
                    
                    if (!$existing) {
                        $paketName = $cust['paket'] ?? null;
                        $packageId = null;
                        if ($paketName) {
                            $pkg = \App\Models\InternetPackage::where('name', $paketName)->first();
                            if ($pkg) $packageId = $pkg->id;
                        }
                        
                        $salesId = $cust['sales_id'] ?? null;
                        if ($salesId) {
                            $user = \App\Models\User::find($salesId);
                            if (!$user) $salesId = null;
                        }

                        $notes = "Sinkronisasi dari App-LK:\nPaket: " . ($paketName ?? '-') . "\nSales ID: " . ($cust['sales_id'] ?? '-');

                        \App\Models\Customer::create([
                            'name' => $cust['name'],
                            'phone' => $cust['phone'],
                            'address' => $cust['address'] ?? '-',
                            'email' => $cust['email'] ?? null,
                            'status' => 'booking',
                            'area' => $cust['area'] ?? $cust['wilayah'] ?? $cust['region'] ?? null,
                            'package_id' => $packageId,
                            'sales_id' => $salesId,
                            'notes' => $notes,
                            'registration_date' => !empty($cust['register_date'])
                                ? \Carbon\Carbon::parse($cust['register_date'])->toDateString()
                                : (!empty($cust['registration_date']) 
                                    ? \Carbon\Carbon::parse($cust['registration_date'])->toDateString() 
                                    : (!empty($cust['created_at']) ? \Carbon\Carbon::parse($cust['created_at'])->toDateString() : null)),
                        ]);
                        $syncedCount++;
                    }
                }

                return response()->json(['status' => 'success', 'message' => "Berhasil menarik dan mendaftarkan $syncedCount pelanggan baru dari app-LK."]);
            }
            return response()->json(['status' => 'error', 'message' => 'Sinkronisasi gagal. HTTP Status: ' . $response->status()]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
