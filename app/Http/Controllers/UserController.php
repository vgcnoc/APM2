<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Area;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $users = User::query()
            ->with(['area', 'roles'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role, function ($query, $role) {
                $query->where('role', $role);
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
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'on_duty' => User::where('is_on_duty', true)->count(),
            'teknisi' => User::whereIn('role', ['teknisi', 'maintenance'])->count(),
            'admin' => User::whereIn('role', ['admin', 'super admin', 'manager'])->count(),
        ];

        // Fetch roles from Spatie with fallback presets
        $spatieRoles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name')->toArray();
        $presetRoles = ['admin', 'teknisi', 'noc', 'cs', 'sales', 'finance', 'maintenance', 'reseller', 'manager', 'hrd'];
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
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|max:50',
            'area_id' => 'nullable|exists:areas,id',
            'is_active' => 'boolean',
            'accessible_areas' => 'nullable|array',
            'base_salary' => 'nullable|numeric|min:0',
            'incentive_rate' => 'nullable|numeric|min:0',
            'booking_fee' => 'nullable|numeric|min:0',
            'installation_fee' => 'nullable|numeric|min:0',
            'bpjs_kesehatan' => 'nullable|boolean',
            'bpjs_kesehatan_no' => 'nullable|string|max:50',
            'bpjs_ketenagakerjaan' => 'nullable|boolean',
            'bpjs_tk_no' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:50',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:100',
        ]);

        $validated['name'] = trim($validated['name']);
        $validated['role'] = strtolower(trim($validated['role']));
        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['accessible_areas'] = $request->input('accessible_areas', []);
        $validated['base_salary'] = $request->input('base_salary', 0);
        $validated['incentive_rate'] = $request->input('incentive_rate', 0);
        $validated['booking_fee'] = $request->input('booking_fee', 0);
        $validated['installation_fee'] = $request->input('installation_fee', 0);
        $validated['bpjs_kesehatan'] = $request->boolean('bpjs_kesehatan');
        $validated['bpjs_ketenagakerjaan'] = $request->boolean('bpjs_ketenagakerjaan');

        $user = User::create($validated);
        
        // Assign Spatie role
        try {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $validated['role'], 'guard_name' => 'web']);
            $user->syncRoles([$validated['role']]);
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
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|max:50',
            'area_id' => 'nullable|exists:areas,id',
            'is_active' => 'boolean',
            'accessible_areas' => 'nullable|array',
            'base_salary' => 'nullable|numeric|min:0',
            'incentive_rate' => 'nullable|numeric|min:0',
            'booking_fee' => 'nullable|numeric|min:0',
            'installation_fee' => 'nullable|numeric|min:0',
            'bpjs_kesehatan' => 'nullable|boolean',
            'bpjs_kesehatan_no' => 'nullable|string|max:50',
            'bpjs_ketenagakerjaan' => 'nullable|boolean',
            'bpjs_tk_no' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:50',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:100',
        ]);

        $validated['name'] = trim($validated['name']);
        $validated['role'] = strtolower(trim($validated['role']));

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['accessible_areas'] = $request->input('accessible_areas', []);
        $validated['base_salary'] = $request->input('base_salary', 0);
        $validated['incentive_rate'] = $request->input('incentive_rate', 0);
        $validated['booking_fee'] = $request->input('booking_fee', 0);
        $validated['installation_fee'] = $request->input('installation_fee', 0);
        $validated['bpjs_kesehatan'] = $request->boolean('bpjs_kesehatan');
        $validated['bpjs_ketenagakerjaan'] = $request->boolean('bpjs_ketenagakerjaan');

        $user->update($validated);
        
        // Sync Spatie role
        try {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $validated['role'], 'guard_name' => 'web']);
            $user->syncRoles([$validated['role']]);
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

        $user->is_active = !$user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "User '{$user->name}' berhasil {$statusText}.");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy($id): RedirectResponse
    {
        $user = $id instanceof User ? $id : User::findOrFail($id);

        // Cegah menghapus user sendiri
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')->with('success', "User '{$name}' berhasil dihapus.");
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
