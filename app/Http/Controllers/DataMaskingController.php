<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DataPrivacyMaskingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class DataMaskingController extends Controller
{
    /**
     * Memastikan hanya Super Admin Holding (Pusat) yang dapat mengakses
     */
    protected function ensureHoldingAccess(): void
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }
        $cId = \App\Models\Role::resolveActiveCompanyId();
        $company = \App\Models\Company::find($cId);
        $isHolding = $cId === 1 || ($company && $company->is_holding);
        $isSuperAdmin = method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : (in_array(strtolower($user->role ?? ''), ['admin', 'super admin', 'superadmin'], true) || $user->hasRole('admin'));

        if (!$isHolding || !$isSuperAdmin) {
            abort(403, 'Fitur Privasi & Target Omset hanya dapat diakses oleh Super Admin Holding (Pusat).');
        }
    }

    /**
     * Show Data Masking Settings Page
     */
    public function index()
    {
        $this->ensureHoldingAccess();

        $settings = DataPrivacyMaskingService::getSettings();
        
        $roles = Role::orderBy('name')->pluck('name')->toArray();
        $users = User::select('id', 'name', 'email', 'role')
            ->orderBy('name')
            ->get();

        return Inertia::render('Settings/DataMasking', [
            'settings' => $settings,
            'roles' => $roles,
            'users' => $users,
            'masking_info' => DataPrivacyMaskingService::getMaskingInfoForUser(),
        ]);
    }

    /**
     * Update Data Masking Settings
     */
    public function update(Request $request)
    {
        $this->ensureHoldingAccess();

        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'mode' => 'required|in:omset,customer_count',
            'target_omset' => 'nullable|numeric|min:0',
            'target_customers' => 'nullable|integer|min:1',
            'selection_method' => 'required|in:fifo,alphabetical,price_asc,price_desc',
            'scope' => 'required|in:all_except_super_admin,specific_accounts,everyone',
            'target_roles' => 'nullable|array',
            'target_roles.*' => 'string',
            'target_users' => 'nullable|array',
            'target_users.*' => 'integer',
            'hide_sidebar_menu' => 'nullable|boolean',
            'modules' => 'nullable|array',
            'modules.*' => 'string',
        ]);

        $summary = DataPrivacyMaskingService::updateSettings($validated);

        return redirect()->back()->with('success', 'Pengaturan penyamaran data & target omset berhasil disimpan dan disinkronkan!');
    }

    /**
     * Live Simulator API for Interactive Calculator
     */
    public function simulate(Request $request)
    {
        $this->ensureHoldingAccess();

        $params = [
            'mode' => $request->get('mode', 'omset'),
            'target_omset' => $request->get('target_omset', 100000000),
            'target_customers' => (int) $request->get('target_customers', 500),
            'selection_method' => $request->get('selection_method', 'fifo'),
        ];

        $result = DataPrivacyMaskingService::simulate($params);

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }

    /**
     * Toggle Preview Mode for Super Admin
     */
    public function togglePreview(Request $request)
    {
        $this->ensureHoldingAccess();

        $user = auth()->user();
        if (!DataPrivacyMaskingService::isSuperAdmin($user)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $current = (bool) session('data_masking_preview_mode', false);
        $newMode = !$current;
        session(['data_masking_preview_mode' => $newMode]);

        return redirect()->back()->with('success', $newMode 
            ? 'Mode simulasi tampilan tersamar AKTIF untuk sesi Anda.' 
            : 'Mode simulasi dinonaktifkan. Menampilkan data riil 100%.');
    }
}

