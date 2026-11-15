<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    /**
     * Tampilkan daftar seluruh mitra / perusahaan
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !method_exists($user, 'isSuperAdmin') || !$user->isSuperAdmin()) {
            abort(403, 'Akses terbatas hanya untuk Super Admin Holding.');
        }

        $companies = Company::withCount(['users', 'customers', 'invoices'])
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'code' => $c->code,
                    'brand_name' => $c->brand_name,
                    'tagline' => $c->tagline,
                    'logo_url' => $c->logo_url ? asset('storage/' . $c->logo_url) : null,
                    'phone' => $c->phone,
                    'email' => $c->email,
                    'address' => $c->address,
                    'status' => $c->status,
                    'max_customers' => $c->max_customers,
                    'allowed_modules' => $c->allowed_modules ?: ['billing', 'customers', 'tickets', 'attendance'],
                    'is_holding' => (bool) $c->is_holding,
                    'users_count' => $c->users_count,
                    'customers_count' => $c->customers_count,
                    'invoices_count' => $c->invoices_count,
                    'payment_banks' => json_decode($c->getSetting('payment_banks', '[]'), true),
                    'invoice_issue_date' => (int) $c->getSetting('invoice_issue_date', 1),
                    'billing_cycle' => $c->getSetting('billing_cycle', 'calendar'),
                    'tax_ppn' => (float) $c->getSetting('tax_ppn', 0),
                    'midtrans_server_key' => $c->getSetting('midtrans_server_key'),
                    'midtrans_merchant_id' => $c->getSetting('midtrans_merchant_id'),
                    'created_at' => $c->created_at ? $c->created_at->format('d M Y') : '-',
                ];
            });

        return Inertia::render('Settings/Companies/Index', [
            'companies' => $companies,
            'active_company_id' => session('active_company_id', 'all'),
            'available_modules' => [
                ['id' => 'customers', 'name' => 'Data Pelanggan & PSB', 'desc' => 'Manajemen pelanggan, survey, instalasi, dan aktivasi'],
                ['id' => 'billing', 'name' => 'Billing & Invoice Mandiri', 'desc' => 'Penerbitan tagihan, rekening bank mitra, dan konfirmasi lunas'],
                ['id' => 'tickets', 'name' => 'Tiket Operasional & Komplain', 'desc' => 'PGK (komplain), PGJ (gangguan jalur), PGS (sigap), CBP (cabut)'],
                ['id' => 'attendance', 'name' => 'Presensi & GPS Karyawan', 'desc' => 'Absensi harian, tracking GPS teknisi, dan izin/sakit'],
                ['id' => 'payroll', 'name' => 'Penggajian & Insentif Internal', 'desc' => 'Hitung gaji dan insentif karyawan mitra'],
                ['id' => 'materials', 'name' => 'Logistik & Stok Gudang Mitra', 'desc' => 'Pendataan stok modem, kabel, dan surat jalan material'],
                ['id' => 'vouchers', 'name' => 'Sistem Hotspot & Voucher', 'desc' => 'Generate dan cetak voucher hotspot WiFi'],
                ['id' => 'resellers', 'name' => 'Mitra Loket & Reseller Kasbon', 'desc' => 'Manajemen loket pembayaran tagihan agen mitra'],
                ['id' => 'network', 'name' => 'Infrastruktur & Data Jaringan', 'desc' => 'Peta GIS, topologi, data OLT, ODC, ODP, ONT, dan router mandiri mitra'],
            ],
        ]);
    }

    /**
     * Daftarkan Mitra Baru + Akun Admin Utama Mitra
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !method_exists($user, 'isSuperAdmin') || !$user->isSuperAdmin()) {
            abort(403, 'Akses terbatas hanya untuk Super Admin Holding.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:companies,code|alpha_dash',
            'brand_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'max_customers' => 'nullable|integer|min:1',
            'allowed_modules' => 'nullable|array',
            'admin_name' => 'required|string|max:100',
            'admin_email' => 'required|email|max:100|unique:users,email',
            'admin_password' => 'required|string|min:6',
            'payment_banks' => 'nullable|array',
            'invoice_issue_date' => 'nullable|integer|min:1|max:28',
            'billing_cycle' => 'nullable|string|in:calendar,anniversary',
            'tax_ppn' => 'nullable|numeric|min:0|max:100',
            'midtrans_server_key' => 'nullable|string',
            'midtrans_merchant_id' => 'nullable|string',
        ]);

        $company = Company::create([
            'name' => trim($validated['name']),
            'code' => strtoupper(trim($validated['code'])),
            'brand_name' => trim($validated['brand_name'] ?? $validated['name']),
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'max_customers' => $validated['max_customers'] ?? null,
            'allowed_modules' => $validated['allowed_modules'] ?? ['billing', 'customers', 'tickets', 'attendance'],
            'status' => 'active',
            'is_holding' => false,
        ]);

        // Simpan setting khusus mitra
        if (!empty($validated['payment_banks'])) {
            $company->setSetting('payment_banks', json_encode($validated['payment_banks']));
        }
        if (isset($validated['invoice_issue_date'])) {
            $company->setSetting('invoice_issue_date', (string) $validated['invoice_issue_date']);
        }
        if (isset($validated['billing_cycle'])) {
            $company->setSetting('billing_cycle', $validated['billing_cycle']);
        }
        if (isset($validated['tax_ppn'])) {
            $company->setSetting('tax_ppn', (string) $validated['tax_ppn']);
        }
        if (!empty($validated['midtrans_server_key'])) {
            $company->setSetting('midtrans_server_key', $validated['midtrans_server_key']);
        }
        if (!empty($validated['midtrans_merchant_id'])) {
            $company->setSetting('midtrans_merchant_id', $validated['midtrans_merchant_id']);
        }

        // Buat Akun Admin Utama untuk Mitra tersebut
        $adminUser = User::create([
            'company_id' => $company->id,
            'name' => trim($validated['admin_name']),
            'email' => strtolower(trim($validated['admin_email'])),
            'password' => Hash::make($validated['admin_password']),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Assign role admin
        if (method_exists($adminUser, 'assignRole')) {
            $adminUser->assignRole('admin');
        }

        return redirect()->back()->with('success', "Mitra '{$company->name}' berhasil didaftarkan bersama akun Admin: {$adminUser->email}");
    }

    /**
     * Perbarui Profil & Pengaturan Mitra
     */
    public function update(Request $request, Company $company)
    {
        $user = $request->user();
        if (!$user || !method_exists($user, 'isSuperAdmin') || !$user->isSuperAdmin()) {
            abort(403, 'Akses terbatas hanya untuk Super Admin Holding.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => ['required', 'string', 'max:20', 'alpha_dash', Rule::unique('companies', 'code')->ignore($company->id)],
            'brand_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'status' => 'required|in:active,suspended,inactive',
            'max_customers' => 'nullable|integer|min:1',
            'allowed_modules' => 'nullable|array',
            'payment_banks' => 'nullable|array',
            'invoice_issue_date' => 'nullable|integer|min:1|max:28',
            'billing_cycle' => 'nullable|string|in:calendar,anniversary',
            'tax_ppn' => 'nullable|numeric|min:0|max:100',
            'midtrans_server_key' => 'nullable|string',
            'midtrans_merchant_id' => 'nullable|string',
        ]);

        $company->update([
            'name' => trim($validated['name']),
            'code' => strtoupper(trim($validated['code'])),
            'brand_name' => trim($validated['brand_name'] ?? $validated['name']),
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => $company->is_holding ? 'active' : $validated['status'],
            'max_customers' => $validated['max_customers'] ?? null,
            'allowed_modules' => $company->is_holding ? null : ($validated['allowed_modules'] ?? []),
        ]);

        // Update settings
        if (isset($validated['payment_banks'])) {
            $company->setSetting('payment_banks', json_encode($validated['payment_banks']));
        }
        if (isset($validated['invoice_issue_date'])) {
            $company->setSetting('invoice_issue_date', (string) $validated['invoice_issue_date']);
        }
        if (isset($validated['billing_cycle'])) {
            $company->setSetting('billing_cycle', $validated['billing_cycle']);
        }
        if (isset($validated['tax_ppn'])) {
            $company->setSetting('tax_ppn', (string) $validated['tax_ppn']);
        }
        if (array_key_exists('midtrans_server_key', $validated)) {
            $company->setSetting('midtrans_server_key', $validated['midtrans_server_key'] ?? '');
        }
        if (array_key_exists('midtrans_merchant_id', $validated)) {
            $company->setSetting('midtrans_merchant_id', $validated['midtrans_merchant_id'] ?? '');
        }

        return redirect()->back()->with('success', "Data Mitra '{$company->name}' berhasil diperbarui.");
    }

    /**
     * Fitur Cerdas: Switch Company View khusus Super Admin Holding
     */
    public function switchCompany(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !method_exists($user, 'isSuperAdmin') || !$user->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin Holding yang dapat beralih tampilan perusahaan.');
        }

        if ($id === 'all' || (int) $id === 0) {
            session()->forget('active_company_id');
            return redirect()->back()->with('success', 'Beralih ke tampilan Holding (Konsolidasi Seluruh Data).');
        }

        $company = Company::findOrFail((int) $id);
        session(['active_company_id' => $company->id]);

        return redirect()->back()->with('success', "Tampilan sekarang disaring khusus untuk Mitra: '{$company->name}'");
    }

    /**
     * Hapus Mitra (Non-holding saja)
     */
    public function destroy(Request $request, Company $company)
    {
        $user = $request->user();
        if (!$user || !method_exists($user, 'isSuperAdmin') || !$user->isSuperAdmin()) {
            abort(403, 'Akses terbatas hanya untuk Super Admin Holding.');
        }

        if ($company->is_holding || $company->id === 1) {
            return redirect()->back()->with('error', 'Perusahaan Pusat (Holding) tidak dapat dihapus.');
        }

        $name = $company->name;
        $company->delete();

        return redirect()->back()->with('success', "Mitra '{$name}' berhasil dinonaktifkan.");
    }
}

