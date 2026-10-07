<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): Response
    {
        $users = User::query()
            ->with('area')
            ->when($request->search, fn($q, $search) => 
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            )
            ->when($request->role, fn($q, $role) => 
                $q->where('role', $role)
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'areas' => Area::orderBy('name')->get(),
            'roles' => \Spatie\Permission\Models\Role::orderBy('name')->pluck('name'),
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'is_active' => 'boolean',
            'accessible_areas' => 'nullable|array',
            'base_salary' => 'nullable|numeric|min:0',
            'incentive_rate' => 'nullable|numeric|min:0',
            'booking_fee' => 'nullable|numeric|min:0',
            'installation_fee' => 'nullable|numeric|min:0',
        ]);

        $employee = \App\Models\Employee::where('email', $validated['email'])->first();

        if (!$employee) {
            return back()->withErrors(['email' => 'Email tidak ditemukan di Data Karyawan.']);
        }

        $validated['name'] = $employee->name;
        $validated['role'] = strtolower($employee->position ?? 'noc');
        $validated['area_id'] = $employee->area_id;

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['accessible_areas'] = $request->input('accessible_areas', []);
        $validated['base_salary'] = $request->input('base_salary', 0);
        $validated['incentive_rate'] = $request->input('incentive_rate', 0);
        $validated['booking_fee'] = $request->input('booking_fee', 0);
        $validated['installation_fee'] = $request->input('installation_fee', 0);

        $user = User::create($validated);
        
        // Assign Spatie role
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $validated['role'], 'guard_name' => 'web']);
        $user->assignRole($validated['role']);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'is_active' => 'boolean',
            'accessible_areas' => 'nullable|array',
            'base_salary' => 'nullable|numeric|min:0',
            'incentive_rate' => 'nullable|numeric|min:0',
            'booking_fee' => 'nullable|numeric|min:0',
            'installation_fee' => 'nullable|numeric|min:0',
        ]);

        $employee = \App\Models\Employee::where('email', $validated['email'])->first();

        if (!$employee) {
            return back()->withErrors(['email' => 'Email tidak ditemukan di Data Karyawan.']);
        }

        $validated['name'] = $employee->name;
        $validated['role'] = strtolower($employee->position ?? 'noc');
        $validated['area_id'] = $employee->area_id;

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

        $user->update($validated);
        
        // Sync Spatie role
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $validated['role'], 'guard_name' => 'web']);
        $user->syncRoles([$validated['role']]);

        return redirect()->route('users.index')->with('success', 'Data user berhasil diperbarui.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Cegah menghapus user sendiri
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
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
