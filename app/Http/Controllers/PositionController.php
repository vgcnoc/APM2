<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class PositionController extends Controller
{
    private const PROTECTED_SYSTEM_ROLES = [
        'admin', 'noc', 'teknisi', 'cs', 'admin cs', 'sales', 'finance', 'hrd',
        'manager', 'maintenance', 'reseller', 'affiliate', 'kepala teknisi',
        'dispatcher', 'customer', 'karyawan', 'gudang', 'marketing', 'auditor', 'aktivator'
    ];

    public function index(Request $request)
    {
        $activeCompanyId = (int) (session('active_company_id') ?: (auth()->user()?->company_id ?: 1));
        if ($activeCompanyId !== 1 && Position::count() === 0) {
            $holdingPositions = Position::withoutGlobalScopes()->where('company_id', 1)->get();
            foreach ($holdingPositions as $hp) {
                $cloned = $hp->replicate();
                $cloned->company_id = $activeCompanyId;
                $cloned->save();
            }
        }

        $query = Position::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('department', 'like', "%{$request->search}%");
            });
        }

        $positions = $query->orderBy('name')->paginate(15)->withQueryString();

        return \Inertia\Inertia::render('Positions/Index', [
            'positions' => $positions,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $activeCompanyId = (int) (session('active_company_id') ?: (auth()->user()?->company_id ?: 1));

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')->where('company_id', $activeCompanyId),
            ],
            'department' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['name'] = trim($validated['name']);
        $validated['company_id'] = $activeCompanyId;

        $position = Position::create($validated);

        // Sync to Role aman (selalu lowercase)
        $roleName = strtolower(trim($position->name));
        Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

        return redirect()->route('positions.index')->with('success', 'Posisi/Jabatan berhasil ditambahkan.');
    }

    public function update(Request $request, Position $position)
    {
        $activeCompanyId = (int) ($position->company_id ?: (session('active_company_id') ?: (auth()->user()?->company_id ?: 1)));

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'name')->where('company_id', $activeCompanyId)->ignore($position->id),
            ],
            'department' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['name'] = trim($validated['name']);

        $oldName = strtolower(trim($position->name));
        $position->update($validated);
        $newName = strtolower(trim($position->name));

        // Sync to Role dengan aman
        if ($oldName !== $newName) {
            // Pastikan role baru tersedia
            $newRole = Role::firstOrCreate(['name' => $newName, 'guard_name' => 'web']);

            // Jika role lama bukan sistem role inti
            if (!in_array($oldName, self::PROTECTED_SYSTEM_ROLES)) {
                $otherPositionExists = Position::withoutGlobalScopes()
                    ->where('id', '!=', $position->id)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [$oldName])
                    ->exists();

                if (!$otherPositionExists) {
                    $oldRole = Role::where('name', $oldName)->where('guard_name', 'web')->first();
                    if ($oldRole && $oldRole->id !== $newRole->id) {
                        // Salin permissions dari oldRole ke newRole jika newRole belum punya permissions
                        if ($newRole->permissions()->count() === 0 && $oldRole->permissions()->count() > 0) {
                            $newRole->syncPermissions($oldRole->permissions);
                        }

                        // Migrasikan user dari oldRole ke newRole (hanya user pada company ini)
                        $companyUsers = $oldRole->users()->where('company_id', $activeCompanyId)->get();
                        foreach ($companyUsers as $u) {
                            $u->removeRole($oldRole);
                            $u->assignRole($newRole);
                            if (strtolower(trim($u->role)) === $oldName) {
                                $u->role = $newName;
                                $u->save();
                            }
                        }

                        // Hapus oldRole kustom jika sudah tidak dipakai sama sekali oleh user manapun
                        if ($oldRole->users()->count() === 0) {
                            $oldRole->delete();
                        }
                    }
                }
            }
        }

        return redirect()->route('positions.index')->with('success', 'Posisi/Jabatan berhasil diperbarui.');
    }

    public function destroy(Position $position)
    {
        $oldName = strtolower(trim($position->name));
        $position->delete();

        // Jangan pernah hapus role sistem inti
        if (!in_array($oldName, self::PROTECTED_SYSTEM_ROLES)) {
            $otherPositionExists = Position::withoutGlobalScopes()->whereRaw('LOWER(TRIM(name)) = ?', [$oldName])->exists();
            if (!$otherPositionExists) {
                $role = Role::where('name', $oldName)->where('guard_name', 'web')->first();
                if ($role && $role->users()->count() === 0) {
                    $role->delete();
                }
            }
        }

        return redirect()->route('positions.index')->with('success', 'Posisi/Jabatan berhasil dihapus.');
    }
}
