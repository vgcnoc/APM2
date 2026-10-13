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
        $appLogo = Setting::get('app_logo');
        $appFavicon = Setting::get('app_favicon');
        $companyLogo = Setting::get('company_logo');
        return Inertia::render('Settings/Branding', [
            'current_app_logo' => $appLogo ? asset('storage/' . $appLogo) : null,
            'current_app_favicon' => $appFavicon ? asset('storage/' . $appFavicon) : null,
            'current_company_logo' => $companyLogo ? asset('storage/' . $companyLogo) : null,
            'current_app_name' => Setting::get('app_name', ''),
            'company_name' => Setting::get('company_name', ''),
            'company_address' => Setting::get('company_address', ''),
            'company_phone' => Setting::get('company_phone', ''),
            'company_email' => Setting::get('company_email', ''),
            'company_website' => Setting::get('company_website', ''),
        ]);
    }

    public function billing()
    {
        return Inertia::render('Settings/Billing', [
            'billing_type' => Setting::get('billing_type', 'prabayar'),
            'prorata_formula' => Setting::get('prorata_formula', 'exact_days'),
            'prorata_first_month' => Setting::get('prorata_first_month', '1') === '1',
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
            'invoice_template' => Setting::get('invoice_template', 'modern'),
        ]);
    }

    public function payroll()
    {
        return Inertia::render('Settings/Payroll', [
            'payroll_cutoff_date' => Setting::get('payroll_cutoff_date', '1'),
            'payroll_payment_date' => Setting::get('payroll_payment_date', '12'),
            'payroll_components' => json_decode(Setting::get('payroll_components', '["insentif", "potongan"]'), true),
            'payroll_note' => Setting::get('payroll_note', ''),
            'payroll_incentive_mode' => Setting::get('payroll_incentive_mode', 'Bulanan (sekali sebulan)'),
        ]);
    }

    public function updatePayroll(Request $request)
    {
        $request->validate([
            'payroll_cutoff_date' => 'required|integer|min:1|max:28',
            'payroll_payment_date' => 'required|integer|min:1|max:28',
            'payroll_components' => 'nullable|array',
            'payroll_note' => 'nullable|string',
            'payroll_incentive_mode' => 'required|string',
        ]);

        Setting::set('payroll_cutoff_date', $request->payroll_cutoff_date);
        Setting::set('payroll_payment_date', $request->payroll_payment_date);
        Setting::set('payroll_components', json_encode($request->payroll_components ?? []));
        Setting::set('payroll_note', $request->payroll_note ?? '');
        Setting::set('payroll_incentive_mode', $request->payroll_incentive_mode);

        return redirect()->back()->with('success', 'Pengaturan sistem gaji berhasil diperbarui.');
    }

    public function attendance()
    {
        return Inertia::render('Settings/Attendance', [
            'settings' => [
                'attendance_late_enabled' => Setting::get('attendance_late_enabled', '1') === '1',
                'attendance_late_amount' => (int) Setting::get('attendance_late_amount', 10000),
                'attendance_late_mode' => Setting::get('attendance_late_mode', 'Per Kejadian'),
                'attendance_late_tolerance' => (int) Setting::get('attendance_late_tolerance', 10),

                'attendance_missing_out_enabled' => Setting::get('attendance_missing_out_enabled', '1') === '1',
                'attendance_missing_out_amount' => (int) Setting::get('attendance_missing_out_amount', 10000),

                'attendance_early_leave_enabled' => Setting::get('attendance_early_leave_enabled', '1') === '1',
                'attendance_early_leave_amount' => (int) Setting::get('attendance_early_leave_amount', 10000),
                'attendance_early_leave_tolerance' => (int) Setting::get('attendance_early_leave_tolerance', 0),

                'attendance_sick_no_cert_enabled' => Setting::get('attendance_sick_no_cert_enabled', '1') === '1',
                'attendance_sick_no_cert_amount' => (int) Setting::get('attendance_sick_no_cert_amount', 20000),

                'attendance_permit_enabled' => Setting::get('attendance_permit_enabled', '1') === '1',
                'attendance_permit_amount' => (int) Setting::get('attendance_permit_amount', 25000),

                'attendance_alpha_enabled' => Setting::get('attendance_alpha_enabled', '1') === '1',
                'attendance_alpha_amount' => (int) Setting::get('attendance_alpha_amount', 35000),

                'attendance_cron_enabled' => Setting::get('attendance_cron_enabled', '1') === '1',
                'attendance_cron_hour' => (int) Setting::get('attendance_cron_hour', 23),
                'attendance_cron_minute' => (int) Setting::get('attendance_cron_minute', 30),

                // REWARD ABSEN TERBAIK
                'attendance_reward_enabled' => Setting::get('attendance_reward_enabled', '1') === '1',
                'attendance_reward_branch_enabled' => Setting::get('attendance_reward_branch_enabled', '1') === '1',
                'attendance_reward_global_enabled' => Setting::get('attendance_reward_global_enabled', '1') === '1',
                'attendance_reward_branch_1' => (int) Setting::get('attendance_reward_branch_1', 200000),
                'attendance_reward_branch_2' => (int) Setting::get('attendance_reward_branch_2', 150000),
                'attendance_reward_branch_3' => (int) Setting::get('attendance_reward_branch_3', 100000),
                'attendance_reward_global_1' => (int) Setting::get('attendance_reward_global_1', 200000),
                'attendance_reward_global_2' => (int) Setting::get('attendance_reward_global_2', 150000),
                'attendance_reward_global_3' => (int) Setting::get('attendance_reward_global_3', 100000),
                'attendance_reward_note' => Setting::get('attendance_reward_note', 'Reward diberikan setiap akhir bulan bersama gaji'),
            ]
        ]);
    }

    public function updateAttendance(Request $request)
    {
        $request->validate([
            'attendance_late_enabled' => 'nullable|boolean',
            'attendance_late_amount' => 'required|numeric|min:0',
            'attendance_late_mode' => 'required|string',
            'attendance_late_tolerance' => 'required|integer|min:0',

            'attendance_missing_out_enabled' => 'nullable|boolean',
            'attendance_missing_out_amount' => 'required|numeric|min:0',

            'attendance_early_leave_enabled' => 'nullable|boolean',
            'attendance_early_leave_amount' => 'required|numeric|min:0',
            'attendance_early_leave_tolerance' => 'required|integer|min:0',

            'attendance_sick_no_cert_enabled' => 'nullable|boolean',
            'attendance_sick_no_cert_amount' => 'required|numeric|min:0',

            'attendance_permit_enabled' => 'nullable|boolean',
            'attendance_permit_amount' => 'required|numeric|min:0',

            'attendance_alpha_enabled' => 'nullable|boolean',
            'attendance_alpha_amount' => 'required|numeric|min:0',

            'attendance_cron_enabled' => 'nullable|boolean',
            'attendance_cron_hour' => 'required|integer|min:0|max:23',
            'attendance_cron_minute' => 'required|integer|min:0|max:59',

            // Reward Validation
            'attendance_reward_enabled' => 'nullable|boolean',
            'attendance_reward_branch_enabled' => 'nullable|boolean',
            'attendance_reward_global_enabled' => 'nullable|boolean',
            'attendance_reward_branch_1' => 'nullable|numeric|min:0',
            'attendance_reward_branch_2' => 'nullable|numeric|min:0',
            'attendance_reward_branch_3' => 'nullable|numeric|min:0',
            'attendance_reward_global_1' => 'nullable|numeric|min:0',
            'attendance_reward_global_2' => 'nullable|numeric|min:0',
            'attendance_reward_global_3' => 'nullable|numeric|min:0',
            'attendance_reward_note' => 'nullable|string',
        ]);

        Setting::set('attendance_late_enabled', $request->boolean('attendance_late_enabled') ? '1' : '0');
        Setting::set('attendance_late_amount', $request->attendance_late_amount);
        Setting::set('attendance_late_mode', $request->attendance_late_mode);
        Setting::set('attendance_late_tolerance', $request->attendance_late_tolerance);

        Setting::set('attendance_missing_out_enabled', $request->boolean('attendance_missing_out_enabled') ? '1' : '0');
        Setting::set('attendance_missing_out_amount', $request->attendance_missing_out_amount);

        Setting::set('attendance_early_leave_enabled', $request->boolean('attendance_early_leave_enabled') ? '1' : '0');
        Setting::set('attendance_early_leave_amount', $request->attendance_early_leave_amount);
        Setting::set('attendance_early_leave_tolerance', $request->attendance_early_leave_tolerance);

        Setting::set('attendance_sick_no_cert_enabled', $request->boolean('attendance_sick_no_cert_enabled') ? '1' : '0');
        Setting::set('attendance_sick_no_cert_amount', $request->attendance_sick_no_cert_amount);

        Setting::set('attendance_permit_enabled', $request->boolean('attendance_permit_enabled') ? '1' : '0');
        Setting::set('attendance_permit_amount', $request->attendance_permit_amount);

        Setting::set('attendance_alpha_enabled', $request->boolean('attendance_alpha_enabled') ? '1' : '0');
        Setting::set('attendance_alpha_amount', $request->attendance_alpha_amount);

        Setting::set('attendance_cron_enabled', $request->boolean('attendance_cron_enabled') ? '1' : '0');
        Setting::set('attendance_cron_hour', $request->attendance_cron_hour);
        Setting::set('attendance_cron_minute', $request->attendance_cron_minute);

        // Save Reward Settings
        Setting::set('attendance_reward_enabled', $request->boolean('attendance_reward_enabled') ? '1' : '0');
        Setting::set('attendance_reward_branch_enabled', $request->boolean('attendance_reward_branch_enabled') ? '1' : '0');
        Setting::set('attendance_reward_global_enabled', $request->boolean('attendance_reward_global_enabled') ? '1' : '0');
        Setting::set('attendance_reward_branch_1', $request->attendance_reward_branch_1 ?? 200000);
        Setting::set('attendance_reward_branch_2', $request->attendance_reward_branch_2 ?? 150000);
        Setting::set('attendance_reward_branch_3', $request->attendance_reward_branch_3 ?? 100000);
        Setting::set('attendance_reward_global_1', $request->attendance_reward_global_1 ?? 200000);
        Setting::set('attendance_reward_global_2', $request->attendance_reward_global_2 ?? 150000);
        Setting::set('attendance_reward_global_3', $request->attendance_reward_global_3 ?? 100000);
        Setting::set('attendance_reward_note', $request->attendance_reward_note ?? '');

        return redirect()->back()->with('success', 'Pengaturan absensi & reward terbaik berhasil disimpan.');
    }

    public function updateBilling(Request $request)
    {
        // 1. Format isolate_time agar aman dari format detik browser (e.g. 23:59:00 -> 23:59)
        if ($request->has('isolate_time') && is_string($request->isolate_time)) {
            $time = trim($request->isolate_time);
            if (preg_match('/^(\d{1,2}:\d{2})(:\d{2})?$/', $time, $matches)) {
                $parts = explode(':', $matches[1]);
                $formattedTime = sprintf('%02d:%02d', (int)$parts[0], (int)$parts[1]);
                $request->merge(['isolate_time' => $formattedTime]);
            }
        }

        // 2. Bersihkan payment_banks dari baris kosong agar tidak memicu error validasi
        if ($request->has('payment_banks') && is_array($request->payment_banks)) {
            $filteredBanks = array_values(array_filter($request->payment_banks, function ($b) {
                if (!is_array($b)) return false;
                $name = trim($b['bank_name'] ?? '');
                $accName = trim($b['account_name'] ?? '');
                $accNum = trim($b['account_number'] ?? '');
                return ($name !== '' || $accName !== '' || $accNum !== '');
            }));
            $request->merge(['payment_banks' => $filteredBanks]);
        }

        // 3. Normalisasi nilai pajak jika kosong atau null
        if ($request->has('tax_ppn') && ($request->tax_ppn === null || $request->tax_ppn === '')) {
            $request->merge(['tax_ppn' => 0]);
        }
        if ($request->has('tax_bhp') && ($request->tax_bhp === null || $request->tax_bhp === '')) {
            $request->merge(['tax_bhp' => 0]);
        }
        if ($request->has('tax_uso') && ($request->tax_uso === null || $request->tax_uso === '')) {
            $request->merge(['tax_uso' => 0]);
        }

        $request->validate([
            'billing_type' => 'sometimes|required|in:prabayar,pascabayar',
            'prorata_formula' => 'nullable|in:exact_days,fixed_30,mid_month',
            'prorata_first_month' => 'nullable|boolean',
            'invoice_issue_date' => 'sometimes|required|integer|min:1|max:28',
            'isolate_days' => 'sometimes|required|integer|min:0',
            'isolate_time' => 'sometimes|required|date_format:H:i',
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
            'invoice_template' => 'nullable|string|in:modern,classic,thermal,minimalist',
        ]);

        if ($request->has('billing_type')) {
            Setting::set('billing_type', $request->billing_type);
        }
        if ($request->has('prorata_formula')) {
            Setting::set('prorata_formula', $request->prorata_formula);
        }
        if ($request->has('prorata_first_month')) {
            Setting::set('prorata_first_month', $request->boolean('prorata_first_month') ? '1' : '0');
        }
        if ($request->has('invoice_issue_date')) {
            Setting::set('invoice_issue_date', $request->invoice_issue_date);
        }
        if ($request->has('isolate_days')) {
            Setting::set('isolate_days', $request->isolate_days);
        }
        if ($request->has('isolate_time')) {
            Setting::set('isolate_time', $request->isolate_time);
        }
        if ($request->has('invoice_template')) {
            Setting::set('invoice_template', $request->invoice_template);
        }
        
        if ($request->has('tax_ppn')) {
            Setting::set('tax_ppn', $request->tax_ppn ?? 0);
        }
        if ($request->has('tax_bhp')) {
            Setting::set('tax_bhp', $request->tax_bhp ?? 0);
        }
        if ($request->has('tax_uso')) {
            Setting::set('tax_uso', $request->tax_uso ?? 0);
        }

        if ($request->has('payment_banks')) {
            Setting::set('payment_banks', json_encode($request->payment_banks ?? []));
        }
        if ($request->has('pg_provider')) {
            Setting::set('pg_provider', $request->pg_provider ?? 'none');
        }
        if ($request->has('pg_environment')) {
            Setting::set('pg_environment', $request->pg_environment ?? 'sandbox');
        }
        if ($request->has('pg_merchant_id')) {
            Setting::set('pg_merchant_id', $request->pg_merchant_id ?? '');
        }
        if ($request->has('pg_api_key')) {
            Setting::set('pg_api_key', $request->pg_api_key ?? '');
        }
        if ($request->has('pg_private_key')) {
            Setting::set('pg_private_key', $request->pg_private_key ?? '');
        }
        if ($request->has('pg_callback_token')) {
            Setting::set('pg_callback_token', $request->pg_callback_token ?? '');
        }

        $section = $request->input('_section');
        $message = $section ? "Pengaturan {$section} berhasil disimpan." : 'Pengaturan billing berhasil diperbarui.';

        return redirect()->back()->with('success', $message);
    }

    public function previewInvoiceTemplate(Request $request)
    {
        $template = $request->query('template', Setting::get('invoice_template', 'modern'));

        $dummyCustomer = (object) [
            'name' => 'Budi Santoso',
            'customer_code' => 'CUST-0089',
            'address' => 'Jl. Merdeka No. 45, RT 02/RW 04, Kel. Sukamaju',
            'phone' => '0812-3456-7890',
            'tax_ppn' => Setting::get('tax_ppn', '0'),
            'package' => (object) [
                'name' => 'Home Ultra 50 Mbps',
                'price' => 250000,
            ],
        ];

        $dummyInvoice = (object) [
            'id' => 9999,
            'invoice_number' => 'INV/' . date('Ym') . '/0089',
            'issued_date' => now(),
            'due_date' => now()->addDays((int) Setting::get('isolate_days', 3)),
            'status' => 'unpaid',
            'amount' => 250000,
            'total_paid' => 0,
            'remaining' => 250000,
            'period_label' => now()->translatedFormat('F Y'),
            'customer' => $dummyCustomer,
            'payments' => collect([]),
        ];

        $company = [
            'name' => Setting::get('company_name', config('app.name', 'ISP Management')),
            'address' => Setting::get('company_address', 'Layanan Internet Cepat & Stabil'),
            'phone' => Setting::get('company_phone', '0811-2233-4455'),
            'email' => Setting::get('company_email', 'billing@ispmanagement.id'),
            'website' => Setting::get('company_website', 'www.ispmanagement.id'),
            'logo' => Setting::get('company_logo', null),
        ];

        $banks = json_decode(Setting::get('payment_banks', '[]'), true) ?: [
            [
                'bank_name' => 'BCA',
                'account_name' => 'PT ISP NETWORK INDONESIA',
                'account_number' => '8830123456',
            ],
            [
                'bank_name' => 'Mandiri',
                'account_name' => 'PT ISP NETWORK INDONESIA',
                'account_number' => '1370009876543',
            ]
        ];

        $isPreview = true;

        return view('print.invoice', [
            'invoice' => $dummyInvoice,
            'company' => $company,
            'banks' => $banks,
            'template' => $template,
            'isPreview' => $isPreview,
        ]);
    }

    public function updateBranding(Request $request)
    {
        \Log::info('updateBranding payload:', $request->all());
        \Log::info('hasFile app_logo:', [$request->hasFile('app_logo')]);
        
        $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_logo' => 'nullable|image|max:2048',
            'remove_app_logo' => 'nullable|boolean',
            'app_favicon' => 'nullable|mimes:jpeg,png,jpg,gif,svg,ico|max:1024',
            'remove_app_favicon' => 'nullable|boolean',
            'company_logo' => 'nullable|image|max:2048',
            'remove_company_logo' => 'nullable|boolean',
            'company_name' => 'nullable|string|max:255',
            'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:255',
            'company_website' => 'nullable|string|max:255',
        ]);

        if ($request->has('app_name')) Setting::set('app_name', $request->app_name ?? '');
        if ($request->has('company_name')) Setting::set('company_name', $request->company_name ?? '');
        if ($request->has('company_address')) Setting::set('company_address', $request->company_address ?? '');
        if ($request->has('company_phone')) Setting::set('company_phone', $request->company_phone ?? '');
        if ($request->has('company_email')) Setting::set('company_email', $request->company_email ?? '');
        if ($request->has('company_website')) Setting::set('company_website', $request->company_website ?? '');

        // Handle App Logo
        if ($request->boolean('remove_app_logo')) {
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

        // Handle App Favicon
        if ($request->boolean('remove_app_favicon')) {
            $oldFavicon = Setting::get('app_favicon');
            if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
                Storage::disk('public')->delete($oldFavicon);
            }
            Setting::set('app_favicon', null);
        } elseif ($request->hasFile('app_favicon')) {
            $oldFavicon = Setting::get('app_favicon');
            if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
                Storage::disk('public')->delete($oldFavicon);
            }
            $favPath = $request->file('app_favicon')->store('logos', 'public');
            Setting::set('app_favicon', $favPath);
        }

        // Handle Company Logo
        if ($request->boolean('remove_company_logo')) {
            $oldCompLogo = Setting::get('company_logo');
            if ($oldCompLogo && Storage::disk('public')->exists($oldCompLogo)) {
                Storage::disk('public')->delete($oldCompLogo);
            }
            Setting::set('company_logo', null);
        } elseif ($request->hasFile('company_logo')) {
            $oldCompLogo = Setting::get('company_logo');
            if ($oldCompLogo && Storage::disk('public')->exists($oldCompLogo)) {
                Storage::disk('public')->delete($oldCompLogo);
            }
            $compPath = $request->file('company_logo')->store('logos', 'public');
            Setting::set('company_logo', $compPath);
        }

        if ($request->ajax() || $request->wantsJson()) {
            $appLogo = Setting::get('app_logo');
            $appFavicon = Setting::get('app_favicon');
            $compLogo = Setting::get('company_logo');
            return response()->json([
                'success' => true,
                'message' => 'Branding berhasil diperbarui.',
                'app_logo' => $appLogo ? asset('storage/' . $appLogo) : null,
                'app_favicon' => $appFavicon ? asset('storage/' . $appFavicon) : null,
                'company_logo' => $compLogo ? asset('storage/' . $compLogo) : null,
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
