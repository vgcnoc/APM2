<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Employee;
use App\Models\User;
use App\Http\Controllers\EmployeeController;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $employees = Employee::all();
        foreach ($employees as $emp) {
            $user = User::where('email', $emp->email)
                ->orWhere(function ($q) use ($emp) {
                    if (!empty($emp->phone)) {
                        $q->where('phone', $emp->phone);
                    }
                })
                ->orWhere('name', $emp->name)
                ->first();

            if ($user && !empty($emp->position)) {
                $targetRole = EmployeeController::mapPositionToRole($emp->position);
                $user->update(['role' => $targetRole]);

                // Also ensure Spatie role exists and is assigned
                $spatieRoleName = strtolower(trim($emp->position));
                try {
                    Role::firstOrCreate(['name' => $spatieRoleName, 'guard_name' => 'web']);
                    if (!$user->hasRole($spatieRoleName)) {
                        $user->assignRole($spatieRoleName);
                    }
                } catch (\Throwable $e) {
                    // Ignore if role issue occurs
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed
    }
};
