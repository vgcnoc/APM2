<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Employee::query();
        
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('iak_number', 'like', "%{$request->search}%")
                  ->orWhere('position', 'like', "%{$request->search}%");
        }

        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('hr_employees_view_all')) {
            if (auth()->user()->can('hr_employees_view_area')) {
                $query->where('area_id', auth()->user()->area_id);
            } else {
                // If they don't have view_all or view_area, they only see themselves (or nothing, depending on logic)
                // Let's just limit to their area anyway, or their own employee record. Let's do their own email.
                $query->where('email', auth()->user()->email);
            }
        }

        $employees = $query->with('area')->latest()->paginate(10)->withQueryString();
        
        $areas = \App\Models\Area::orderBy('name')->get();
        $positions = \App\Models\Position::orderBy('name')->get();

        return \Inertia\Inertia::render('Employees/Index', [
            'employees' => $employees,
            'areas' => $areas,
            'positions' => $positions,
            'filters' => $request->only(['search'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'iak_number' => 'nullable|string|max:255|unique:employees,iak_number',
            'position' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:employees,email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'area_id' => 'nullable|exists:areas,id',
            'branch' => 'nullable|string|max:255',
            'join_date' => 'nullable|date',
            'employee_type' => 'nullable|string|max:50',
            'payment_method' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('employees', 'public');
            $validated['photo'] = $path;
        }

        \App\Models\Employee::create($validated);

        // Auto-create user
        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt('password123'), // Default password
            'area_id' => $validated['area_id'] ?? null,
        ]);

        if (!empty($validated['position'])) {
            $roleName = strtolower($validated['position']);
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $user->syncRoles([$roleName]);
        }

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, \App\Models\Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'iak_number' => 'nullable|string|max:255|unique:employees,iak_number,' . $employee->id,
            'position' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:20',
            'area_id' => 'nullable|exists:areas,id',
            'branch' => 'nullable|string|max:255',
            'join_date' => 'nullable|date',
            'employee_type' => 'nullable|string|max:50',
            'payment_method' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        // Validate uniqueness in users table except for the user that corresponds to this employee's old email
        $existingUser = \App\Models\User::where('email', $employee->email)->first();
        if ($existingUser) {
            $request->validate([
                'email' => 'unique:users,email,' . $existingUser->id,
            ]);
        } else {
            $request->validate([
                'email' => 'unique:users,email',
            ]);
        }

        if ($request->hasFile('photo')) {
            if ($employee->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($employee->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($employee->photo);
            }
            $path = $request->file('photo')->store('employees', 'public');
            $validated['photo'] = $path;
        }

        $oldEmail = $employee->email;
        $employee->update($validated);

        // Auto-update user
        if ($oldEmail) {
            $user = \App\Models\User::where('email', $oldEmail)->first();
            if ($user) {
                $user->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'area_id' => $validated['area_id'] ?? null,
                ]);
                
                if (!empty($validated['position'])) {
                    $roleName = strtolower($validated['position']);
                    \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                    $user->syncRoles([$roleName]);
                }
            }
        }

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(\App\Models\Employee $employee)
    {
        if ($employee->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($employee->photo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($employee->photo);
        }
        
        $employee->delete();
        
        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil dihapus.');
    }
}
