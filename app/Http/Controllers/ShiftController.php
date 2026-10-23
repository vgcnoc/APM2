<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    /**
     * Tampilkan daftar master shift kerja
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();
        if ($user && !$user->hasRole('admin') && !$user->can('menu_shifts')) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka Master Shift Kerja.');
        }

        $query = Shift::withCount('employees');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('name', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $shifts = $query->orderBy('start_time')->paginate(15)->withQueryString();

        $allShifts = Shift::all();
        $stats = [
            'total_shifts' => $allShifts->count(),
            'active_shifts' => $allShifts->where('is_active', true)->count(),
            'inactive_shifts' => $allShifts->where('is_active', false)->count(),
            'total_assigned_employees' => Employee::whereNotNull('shift_id')->count(),
            'total_employees' => Employee::count(),
        ];

        // Daftar karyawan untuk modal assign shift
        $employees = Employee::select('id', 'name', 'position', 'branch', 'shift_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('Shifts/Index', [
            'shifts' => $shifts,
            'stats' => $stats,
            'employees' => $employees,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Simpan shift kerja baru
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'color' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'Nama shift wajib diisi.',
            'start_time.required' => 'Jam masuk wajib ditentukan.',
            'end_time.required' => 'Jam keluar wajib ditentukan.',
        ]);

        // Format waktu agar konsisten H:i (e.g. "08:00")
        $start = substr($validated['start_time'], 0, 5);
        $end = substr($validated['end_time'], 0, 5);

        $validated['start_time'] = $start;
        $validated['end_time'] = $end;
        $validated['is_night_shift'] = $end < $start;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['color'] = $validated['color'] ?? 'blue';

        Shift::create($validated);

        return redirect()->route('shifts.index')
            ->with('success', 'Shift kerja berhasil ditambahkan.');
    }

    /**
     * Perbarui data shift kerja
     */
    public function update(Request $request, Shift $shift): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|string|max:10',
            'end_time' => 'required|string|max:10',
            'color' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'Nama shift wajib diisi.',
            'start_time.required' => 'Jam masuk wajib ditentukan.',
            'end_time.required' => 'Jam keluar wajib ditentukan.',
        ]);

        $start = substr($validated['start_time'], 0, 5);
        $end = substr($validated['end_time'], 0, 5);

        $validated['start_time'] = $start;
        $validated['end_time'] = $end;
        $validated['is_night_shift'] = $end < $start;
        $validated['is_active'] = $request->boolean('is_active', true);

        $shift->update($validated);

        return redirect()->route('shifts.index')
            ->with('success', 'Shift kerja berhasil diperbarui.');
    }

    /**
     * Hapus shift kerja
     */
    public function destroy(Shift $shift): RedirectResponse
    {
        // Lepas shift dari karyawan terkait
        Employee::where('shift_id', $shift->id)->update(['shift_id' => null]);

        $shift->delete();

        return redirect()->route('shifts.index')
            ->with('success', 'Shift kerja berhasil dihapus.');
    }

    /**
     * Tugaskan karyawan ke shift ini
     */
    public function assignEmployees(Request $request, Shift $shift): RedirectResponse
    {
        $validated = $request->validate([
            'employee_ids' => 'nullable|array',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $employeeIds = $validated['employee_ids'] ?? [];

        // Lepas karyawan lama yang ada di shift ini jika tidak dipilih lagi
        Employee::where('shift_id', $shift->id)
            ->whereNotIn('id', $employeeIds)
            ->update(['shift_id' => null]);

        // Pasang karyawan yang baru dipilih
        if (!empty($employeeIds)) {
            Employee::whereIn('id', $employeeIds)
                ->update(['shift_id' => $shift->id]);
        }

        return redirect()->route('shifts.index')
            ->with('success', 'Penugasan karyawan pada shift berhasil disimpan.');
    }
}
