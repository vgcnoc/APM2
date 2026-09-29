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

        $employees = $query->latest()->paginate(10)->withQueryString();
        
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
            'email' => 'nullable|email|max:255|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'base_salary' => 'nullable|numeric|min:0',
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

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, \App\Models\Employee $employee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'iak_number' => 'nullable|string|max:255|unique:employees,iak_number,' . $employee->id,
            'position' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:20',
            'base_salary' => 'nullable|numeric|min:0',
            'branch' => 'nullable|string|max:255',
            'join_date' => 'nullable|date',
            'employee_type' => 'nullable|string|max:50',
            'payment_method' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($employee->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($employee->photo)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($employee->photo);
            }
            $path = $request->file('photo')->store('employees', 'public');
            $validated['photo'] = $path;
        }

        $employee->update($validated);

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
