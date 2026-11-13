<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Area;
use App\Models\Employee;
use App\Models\Customer;
use App\Models\AffiliateProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $role = $request->query('role');
        $status = $request->query('status'); // 'all', 'active', 'inactive', 'on_duty'
        $areaId = $request->query('area_id');
        $type = $request->query('type'); // 'all', 'staff', 'customer'

        $users = User::query()
            ->with(['area', 'roles'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($role, function ($query, $role) {
                $query->where('role', $role);
            })
            ->when($areaId, function ($query, $areaId) {
                $query->where('area_id', $areaId);
            })
            ->when($status, function ($query, $status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                } elseif ($status === 'on_duty') {
                    $query->where('is_on_duty', true);
                }
            })
            ->when($type === 'staff', function ($query) {
                $query->where('role', '!=', 'customer');
            })
            ->when($type === 'customer', function ($query) {
                $query->where('role', 'customer');
            })
            // Filter out corrupted users with empty name or '-'
            ->where(function ($q) {
                $q->where('name', '!=', '-')
                  ->where('email', 'not like', '@%');
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => User::where('name', '!=', '-')->where('email', 'not like', '@%')->count(),
            'active' => User::where('name', '!=', '-')->where('email', 'not like', '@%')->where('is_active', true)->count(),
            'inactive' => User::where('name', '!=', '-')->where('email', 'not like', '@%')->where('is_active', false)->count(),
            'on_duty' => User::where('name', '!=', '-')->where('email', 'not like', '@%')->where('is_on_duty', true)->count(),
            'teknisi' => User::where('name', '!=', '-')
                ->where(function ($q) {
                    $q->where('role', 'like', '%teknisi%')
                      ->orWhere('role', 'like', '%maintenance%')
                      ->orWhere('role', 'like', '%infrastruktur%');
                })->count(),
            'admin' => User::where('name', '!=', '-')
                ->where(function ($q) {
                    $q->where('role', 'like', '%admin%')
                      ->orWhere('role', 'like', '%manager%');
                })->count(),
        ];

        // Fetch roles from Spatie with fallback presets
        $spatieRoles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name')->toArray();
        $presetRoles = ['admin', 'teknisi', 'teknisi-2', 'teknisi tim serbu', 'noc', 'cs', 'sales', 'affiliate', 'finance', 'maintenance', 'maintenance-1', 'infrastruktur', 'reseller', 'manager', 'hrd', 'customer'];
        $allRoles = array_values(array_unique(array_merge($spatieRoles, $presetRoles)));
        sort($allRoles);

        // Fetch active employees for easy selection / sync
        $employees = Employee::where(function ($q) {
                $q->where('status', '!=', 'resigned')->orWhereNull('status');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'position', 'area_id', 'base_salary']);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'stats' => $stats,
            'areas' => Area::orderBy('name')->get(['id', 'name']),
            'roles' => $allRoles,
            'employees' => $employees,
            'filters' => $request->only(['search', 'role', 'status', 'area_id', 'type']),
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|max:50',
            'area_id' => 'nullable|exists:areas,id',
            'is_active' => 'boolean',
            'accessible_areas' => 'nullable|array',
            'accessible_areas.*' => 'exists:areas,id',
        ]);

        $validated['name'] = trim($validated['name']);
        if ($validated['name'] === '-' || strlen($validated['name']) < 2) {
            return back()->withErrors(['name' => 'Nama pengguna tidak valid.']);
        }

        $validated['role'] = strtolower(trim($validated['role']));
        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['accessible_areas'] = $request->input('accessible_areas', []);
        $validated['job_position'] = ucwords($validated['role']);

        $user = User::create($validated);
        
        // Assign Spatie role & clear cache
        try {
            $roleModel = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $validated['role'], 'guard_name' => 'web']);
            $user->syncRoles([$roleModel]);
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            \Illuminate\Support\Facades\Cache::put('inertia_rbac_ver', time(), 86400);
        } catch (\Throwable $e) {
            Log::warning('Spatie role sync warning on user store: ' . $e->getMessage());
        }

        return redirect()->route('users.index')->with('success', "User '{$user->name}' berhasil ditambahkan.");
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $user = $id instanceof User ? $id : User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|max:50',
            'area_id' => 'nullable|exists:areas,id',
            'is_active' => 'boolean',
            'accessible_areas' => 'nullable|array',
            'accessible_areas.*' => 'exists:areas,id',
        ]);

        $validated['name'] = trim($validated['name']);
        if ($validated['name'] === '-' || strlen($validated['name']) < 2) {
            return back()->withErrors(['name' => 'Nama pengguna tidak valid.']);
        }

        $validated['role'] = strtolower(trim($validated['role']));

        // Cegah admin menonaktifkan akun sendiri
        if (auth()->id() === $user->id && $request->has('is_active') && !$request->boolean('is_active')) {
            return back()->withErrors(['is_active' => 'Anda tidak dapat menonaktifkan akun yang sedang login.']);
        }

        // Cegah super admin utama diubah rolenya menjadi non-admin
        if ($user->id === 1 && $validated['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Role Super Admin utama tidak dapat diubah.']);
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['accessible_areas'] = $request->input('accessible_areas', []);
        $validated['job_position'] = ucwords($validated['role']);

        $user->update($validated);
        
        // Sync Spatie role & clear cache
        try {
            $roleModel = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $validated['role'], 'guard_name' => 'web']);
            $user->syncRoles([$roleModel]);
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            \Illuminate\Support\Facades\Cache::put('inertia_rbac_ver', time(), 86400);
        } catch (\Throwable $e) {
            Log::warning('Spatie role sync warning on user update: ' . $e->getMessage());
        }

        return redirect()->route('users.index')->with('success', "Data user '{$user->name}' berhasil diperbarui.");
    }

    /**
     * Toggle status aktif user
     */
    public function toggleActive($id): RedirectResponse
    {
        $user = $id instanceof User ? $id : User::findOrFail($id);

        // Prevent deactivating own account
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        // Prevent deactivating primary super admin
        if ($user->id === 1 || in_array(strtolower($user->email), ['admin@isp.local', 'administrator@apm.com'])) {
            return back()->with('error', 'Akun Super Admin utama tidak dapat dinonaktifkan.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        \Illuminate\Support\Facades\Cache::put('inertia_rbac_ver', time(), 86400);

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "User '{$user->name}' berhasil {$statusText}.");
    }

    /**
     * Toggle status on duty for a specific user (admin action)
     */
    public function toggleUserDuty($id): RedirectResponse
    {
        $user = $id instanceof User ? $id : User::findOrFail($id);
        $user->is_on_duty = !$user->is_on_duty;
        $user->save();

        $statusText = $user->is_on_duty ? 'ON DUTY' : 'OFF DUTY';
        return back()->with('success', "Status tugas user '{$user->name}' diubah menjadi {$statusText}.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy($id): RedirectResponse
    {
        $user = $id instanceof User ? $id : User::findOrFail($id);

        // 1. Cegah menghapus user sendiri
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // 2. Cegah menghapus Super Admin utama
        if ($user->id === 1 || in_array(strtolower($user->email), ['admin@isp.local', 'administrator@apm.com'])) {
            return back()->with('error', 'Akun Super Admin utama tidak dapat dihapus.');
        }

        // 3. Cegah menghapus admin terakhir jika hanya tersisa 1 admin aktif
        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus satu-satunya Administrator yang tersisa.');
        }

        $name = $user->name;

        try {
            DB::transaction(function () use ($user) {
                // Lepaskan relasi pelanggan sales jika ada
                Customer::where('sales_id', $user->id)->update(['sales_id' => null]);

                // Lepaskan relasi user pada customer portal jika ada
                Customer::where('user_id', $user->id)->update(['user_id' => null]);

                // Lepaskan relasi karyawan jika ada
                Employee::where('user_id', $user->id)->update(['user_id' => null]);

                // Lepaskan profil afiliasi jika ada
                AffiliateProfile::where('user_id', $user->id)->delete();

                // Lepaskan Spatie roles & permissions
                $user->roles()->detach();
                $user->permissions()->detach();

                // Hapus user
                $user->delete();
            });

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            \Illuminate\Support\Facades\Cache::put('inertia_rbac_ver', time(), 86400);

            return back()->with('success', "User '{$name}' berhasil dihapus.");
        } catch (\Throwable $e) {
            Log::error("Gagal menghapus user {$user->id}: " . $e->getMessage());
            return back()->with('error', "User '{$name}' tidak dapat dihapus karena masih terhubung dengan riwayat operasional (tiket/transaksi/log). Disarankan untuk menonaktifkan status akun.");
        }
    }

    /**
     * Toggle status on duty for authenticated user
     */
    public function toggleDuty(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->is_on_duty = !$user->is_on_duty;
            $user->save();
        }
        
        return back();
    }
}
