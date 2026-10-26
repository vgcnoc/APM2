<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('menu_hr_employees') && !auth()->user()->can('hr_employees_view')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengakses halaman data karyawan.');
        }

        $user = auth()->user();
        $query = \App\Models\Employee::query();

        // Scope Hak Akses Data Karyawan (all, area, own)
        \App\Services\DataScopeService::applyEmployeeScope($query, $user);
        $scopeLevel = \App\Services\DataScopeService::getScopeLevel($user, 'hr_employees');
        
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('iak_number', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('area', fn($aq) => $aq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->filled('employee_type')) {
            $query->where('employee_type', $request->employee_type);
        }

        // Filter Status: active (default), resigned, or all
        $statusFilter = $request->input('status', 'active');
        if ($statusFilter === 'active') {
            $query->where(function($q) {
                $q->where('status', 'active')->orWhereNull('status');
            });
        } elseif ($statusFilter === 'resigned') {
            $query->where('status', 'resigned');
        }

        if ($request->filled('tenure')) {
            $today = \Carbon\Carbon::now()->startOfDay();
            if ($request->tenure === 'senior') {
                $query->whereNotNull('join_date')->where('join_date', '<=', $today->copy()->subYears(3));
            } elseif ($request->tenure === 'middle') {
                $query->whereNotNull('join_date')->whereBetween('join_date', [$today->copy()->subYears(3), $today->copy()->subYear()]);
            } elseif ($request->tenure === 'junior') {
                $query->whereNotNull('join_date')->whereBetween('join_date', [$today->copy()->subYear(), $today->copy()->subMonths(3)]);
            } elseif ($request->tenure === 'new') {
                $query->whereNotNull('join_date')->where('join_date', '>=', $today->copy()->subMonths(3));
            } elseif ($request->tenure === 'unset') {
                $query->whereNull('join_date');
            }
        }

        // Sorting
        if ($request->sort === 'senior') {
            $query->orderByRaw('CASE WHEN join_date IS NULL THEN 1 ELSE 0 END, join_date ASC');
        } elseif ($request->sort === 'newest_joined') {
            $query->orderByRaw('CASE WHEN join_date IS NULL THEN 1 ELSE 0 END, join_date DESC');
        } elseif ($request->sort === 'name_asc') {
            $query->orderBy('name', 'asc');
        } else {
            $query->latest();
        }

        $employees = $query->with('area')->paginate(10)->withQueryString();

        // Isi data bank dari tabel users jika belum terisi di employees
        $employees->getCollection()->transform(function ($emp) {
            if (!$emp->bank_name || !$emp->bank_account_number) {
                $user = \App\Models\User::where('email', $emp->email)->first();
                if ($user) {
                    if (!$emp->bank_name) $emp->bank_name = $user->bank_name;
                    if (!$emp->bank_account_number) $emp->bank_account_number = $user->bank_account_number;
                    if (!$emp->bank_account_holder) $emp->bank_account_holder = $user->bank_account_holder;
                }
            }
            return $emp;
        });
        
        $areaQuery = \App\Models\Area::orderBy('name');
        if ($user && ($scopeLevel === 'area' || $scopeLevel === 'own')) {
            $areaQuery->whereIn('id', $user->getAccessibleAreaIds());
        }
        $areas = $areaQuery->get();
        $positions = \App\Models\Position::orderBy('name')->get();

        // Hitung rata-rata masa kerja seluruh karyawan yang memiliki tanggal gabung
        $now = \Carbon\Carbon::now()->startOfDay();
        $joinDates = \App\Models\Employee::whereNotNull('join_date')->pluck('join_date');
        $avgTenureText = '-';
        if ($joinDates->count() > 0) {
            $totalDays = $joinDates->reduce(function ($carry, $date) use ($now) {
                $c = \Carbon\Carbon::parse($date)->startOfDay();
                return $carry + ($c->isPast() ? $c->diffInDays($now) : 0);
            }, 0);
            $avgDays = (int) round($totalDays / $joinDates->count());
            $years = floor($avgDays / 365);
            $months = floor(($avgDays % 365) / 30);
            if ($years > 0) {
                $avgTenureText = "{$years} Thn " . ($months > 0 ? "{$months} Bln" : "");
            } elseif ($months > 0) {
                $avgTenureText = "{$months} Bulan";
            } else {
                $avgTenureText = "{$avgDays} Hari";
            }
        }

        // Hitung total counts berdasarkan status (dalam scope akses user)
        $baseQuery = \App\Models\Employee::query();
        \App\Services\DataScopeService::applyEmployeeScope($baseQuery, $user);

        $totalActive = (clone $baseQuery)->where(function($q) { $q->where('status', 'active')->orWhereNull('status'); })->count();
        $totalResigned = (clone $baseQuery)->where('status', 'resigned')->count();
        $totalAll = (clone $baseQuery)->count();

        return \Inertia\Inertia::render('Employees/Index', [
            'employees' => $employees,
            'areas' => $areas,
            'positions' => $positions,
            'avg_tenure' => trim($avgTenureText),
            'counts' => [
                'active' => $totalActive,
                'resigned' => $totalResigned,
                'all' => $totalAll,
            ],
            'scopeLevel' => $scopeLevel,
            'filters' => array_filter($request->only(['search', 'area_id', 'employee_type', 'tenure', 'sort', 'status']), fn($v) => !is_null($v) && $v !== '')
        ]);
    }

    public function show(\App\Models\Employee $employee)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->can('hr_employees_view') && !$user->can('menu_hr_employees')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat detail data karyawan.');
        }

        $scopeLevel = \App\Services\DataScopeService::getScopeLevel($user, 'hr_employees');
        if ($scopeLevel === 'area') {
            if ($employee->area_id && !in_array($employee->area_id, $user->getAccessibleAreaIds())) {
                abort(403, 'Anda tidak memiliki hak akses untuk melihat data karyawan di luar area Anda.');
            }
        } elseif ($scopeLevel === 'own') {
            $isOwn = ($employee->email === $user->email)
                || (!empty($user->phone) && $employee->phone === $user->phone)
                || (strcasecmp($employee->name, $user->name) === 0);
            if (!$isOwn) {
                abort(403, 'Anda hanya memiliki hak akses untuk melihat profil data diri Anda sendiri.');
            }
        }

        return response()->json($employee->load('area'));
    }

    public function store(Request $request)
    {
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('hr_employees_create')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambah data karyawan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'iak_number' => 'nullable|string|max:255|unique:employees,iak_number',
            'position' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:employees,email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'area_id' => 'nullable|exists:areas,id',
            'join_date' => 'nullable|date',
            'employee_type' => 'nullable|string|max:50',
            'base_salary' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:50',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('employees', 'public');
            $validated['photo'] = $path;
        }

        \App\Models\Employee::create($validated);

        // Auto-create user
        $roleName = !empty($validated['position']) ? self::mapPositionToRole($validated['position']) : 'cs';
        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $roleName,
            'password' => bcrypt('password123'), // Default password
            'area_id' => $validated['area_id'] ?? null,
            'base_salary' => (float) ($validated['base_salary'] ?? 0),
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'bank_account_holder' => $validated['bank_account_holder'] ?? null,
        ]);

        if (!empty($validated['position'])) {
            $targetRole = self::mapPositionToRole($validated['position']);
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $targetRole, 'guard_name' => 'web']);
            $user->syncRoles([$targetRole]);
        }

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, \App\Models\Employee $employee)
    {
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('hr_employees_update') && !auth()->user()->can('hr_employees_edit')) {
            abort(403, 'Anda tidak memiliki hak akses untuk memperbarui data karyawan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'iak_number' => 'nullable|string|max:255|unique:employees,iak_number,' . $employee->id,
            'position' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:20',
            'area_id' => 'nullable|exists:areas,id',
            'join_date' => 'nullable|date',
            'employee_type' => 'nullable|string|max:50',
            'base_salary' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:50',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_holder' => 'nullable|string|max:100',
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
                $roleName = !empty($validated['position']) ? self::mapPositionToRole($validated['position']) : ($user->role ?: 'cs');
                $userUpdate = [
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'role' => $roleName,
                    'area_id' => $validated['area_id'] ?? null,
                    'bank_name' => $validated['bank_name'] ?? null,
                    'bank_account_number' => $validated['bank_account_number'] ?? null,
                    'bank_account_holder' => $validated['bank_account_holder'] ?? null,
                ];
                if (isset($validated['base_salary'])) {
                    $userUpdate['base_salary'] = (float) $validated['base_salary'];
                }
                $user->update($userUpdate);
                
                if (!empty($validated['position'])) {
                    $targetRole = self::mapPositionToRole($validated['position']);
                    \Spatie\Permission\Models\Role::firstOrCreate(['name' => $targetRole, 'guard_name' => 'web']);
                    $user->syncRoles([$targetRole]);
                }
            }
        }

        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(\App\Models\Employee $employee)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->can('hr_employees_delete') && !$user->can('hr_employees_delete_all')) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus data karyawan.');
        }

        $scopeLevel = \App\Services\DataScopeService::getScopeLevel($user, 'hr_employees');
        if ($scopeLevel === 'area') {
            if ($employee->area_id && !in_array($employee->area_id, $user->getAccessibleAreaIds())) {
                abort(403, 'Anda tidak memiliki hak akses untuk menghapus karyawan di luar area Anda.');
            }
        } elseif ($scopeLevel === 'own') {
            abort(403, 'Anda tidak diperkenankan menghapus profil Anda sendiri.');
        }

        if ($employee->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($employee->photo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($employee->photo);
        }
        
        $employee->delete();
        
        return redirect()->route('employees.index')->with('success', 'Data karyawan berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->can('hr_employees_delete') && !$user->can('hr_employees_delete_all')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk menghapus data karyawan.');
        }

        $query = \App\Models\Employee::query();
        \App\Services\DataScopeService::applyEmployeeScope($query, $user);

        $isAll = $request->boolean('all', false);
        $ids = $request->input('ids', []);

        if (!empty($ids) && is_array($ids)) {
            $query->whereIn('id', $ids);
        } elseif (!$isAll) {
            return redirect()->back()->with('error', 'Tidak ada data karyawan yang dipilih untuk dihapus.');
        }

        // Apply filters if deleting all filtered
        if ($isAll) {
            if ($request->filled('search')) {
                $search = strtolower($request->search);
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('iak_number', 'like', "%{$search}%")
                      ->orWhere('position', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            if ($request->filled('area_id')) {
                $query->where('area_id', $request->area_id);
            }
            if ($request->filled('employee_type')) {
                $query->where('employee_type', $request->employee_type);
            }
        }

        $employees = $query->get();
        $count = $employees->count();

        if ($count === 0) {
            return redirect()->back()->with('error', 'Tidak ada data karyawan yang cocok untuk dihapus.');
        }

        $deleteUsers = $request->boolean('delete_users', false);

        \DB::transaction(function () use ($employees, $deleteUsers) {
            foreach ($employees as $employee) {
                if ($employee->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($employee->photo)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($employee->photo);
                }

                if ($deleteUsers && !empty($employee->email)) {
                    $user = \App\Models\User::where('email', $employee->email)->first();
                    if ($user && $user->id !== auth()->id() && !$user->hasRole('admin')) {
                        $user->delete();
                    }
                }

                $employee->delete();
            }
        });

        return redirect()->route('employees.index')->with('success', "{$count} data karyawan berhasil dihapus.");
    }

    public function resign(\App\Models\Employee $employee, Request $request)
    {
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('hr_employees_resign') && !auth()->user()->can('hr_employees_update')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk memproses karyawan resign.');
        }

        $validated = $request->validate([
            'resign_date' => 'required|date',
            'resign_reason' => 'required|string|max:255',
            'resign_notes' => 'nullable|string|max:1000',
            'deactivate_user' => 'nullable|boolean',
            'clean_schedules' => 'nullable|boolean',
        ]);

        \DB::transaction(function () use ($employee, $validated) {
            $employee->update([
                'status' => 'resigned',
                'resign_date' => $validated['resign_date'],
                'resign_reason' => $validated['resign_reason'],
                'resign_notes' => $validated['resign_notes'] ?? null,
            ]);

            // Deactivate associated User login
            $deactivateUser = $validated['deactivate_user'] ?? true;
            if ($deactivateUser && !empty($employee->email)) {
                $user = \App\Models\User::where('email', $employee->email)->first();
                if ($user && $user->id !== auth()->id() && !$user->hasRole('admin')) {
                    $user->update([
                        'is_active' => false,
                        'is_on_duty' => false,
                    ]);
                }
            }

            // Clean upcoming work schedules
            $cleanSchedules = $validated['clean_schedules'] ?? true;
            if ($cleanSchedules) {
                \App\Models\WorkSchedule::where('employee_id', $employee->id)
                    ->where('date', '>=', $validated['resign_date'])
                    ->delete();
            }
        });

        return redirect()->back()->with('success', "Karyawan {$employee->name} berhasil ditandai Resign. Akun login telah dinonaktifkan dan jadwal mendatang telah disesuaikan.");
    }

    public function reactivate(\App\Models\Employee $employee, Request $request)
    {
        if (auth()->check() && !auth()->user()->hasRole('admin') && !auth()->user()->can('hr_employees_reactivate') && !auth()->user()->can('hr_employees_update')) {
            return redirect()->back()->with('error', 'Anda tidak memiliki hak akses untuk mengaktifkan kembali karyawan.');
        }

        \DB::transaction(function () use ($employee, $request) {
            $employee->update([
                'status' => 'active',
                'resign_date' => null,
                'resign_reason' => null,
                'resign_notes' => null,
            ]);

            // Reactivate user if exists
            if (!empty($employee->email)) {
                $user = \App\Models\User::where('email', $employee->email)->first();
                if ($user) {
                    $user->update([
                        'is_active' => true,
                    ]);
                }
            }
        });

        return redirect()->back()->with('success', "Karyawan {$employee->name} berhasil diaktifkan kembali.");
    }

    /**
     * Map posisi pekerjaan HR ke kode role sistem APM2
     */
    public static function mapPositionToRole(?string $pos): string
    {
        $p = strtolower(trim((string)$pos));
        if (str_contains($p, 'teknisi') || str_contains($p, 'tim serbu') || str_contains($p, 'infrastruktur')) return 'teknisi';
        if (str_contains($p, 'maintenance')) return 'maintenance';
        if (str_contains($p, 'sales') || str_contains($p, 'kolektor')) return 'sales';
        if (str_contains($p, 'finance') || str_contains($p, 'keuangan')) return 'finance';
        if (str_contains($p, 'hrd')) return 'hrd';
        if (str_contains($p, 'noc')) return 'noc';
        if (str_contains($p, 'manajer') || str_contains($p, 'manager')) return 'manager';
        if (str_contains($p, 'customer care') || str_contains($p, 'cs')) return 'cs';
        if (str_contains($p, 'admin') || str_contains($p, 'administrasi')) return 'admin';
        return $p ?: 'karyawan';
    }
}
