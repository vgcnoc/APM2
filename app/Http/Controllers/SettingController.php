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
    public function billing()
    {
        return Inertia::render('Settings/Billing', [
            'billing_type' => Setting::get('billing_type', 'prabayar'),
            'invoice_issue_date' => Setting::get('invoice_issue_date', '1'),
            'due_date_days' => Setting::get('due_date_days', '7'),
            'isolate_days' => Setting::get('isolate_days', '3'),
        ]);
    }

    public function updateBilling(Request $request)
    {
        $request->validate([
            'billing_type' => 'required|in:prabayar,pascabayar,prorata',
            'invoice_issue_date' => 'required|integer|min:1|max:28',
            'due_date_days' => 'required|integer|min:0',
            'isolate_days' => 'required|integer|min:0',
        ]);

        Setting::set('billing_type', $request->billing_type);
        Setting::set('invoice_issue_date', $request->invoice_issue_date);
        Setting::set('due_date_days', $request->due_date_days);
        Setting::set('isolate_days', $request->isolate_days);

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
