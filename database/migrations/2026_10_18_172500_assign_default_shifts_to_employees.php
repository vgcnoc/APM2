<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Shift;
use App\Models\Employee;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $shiftTeknisi = Shift::where('name', 'like', '%Teknisi%')->first() ?: Shift::first();
        $shiftAdmin = Shift::where('name', 'like', '%Admin%')->first() ?: Shift::first();
        $shiftCs = Shift::where('name', 'like', '%CS%')->first() ?: Shift::first();

        $employees = Employee::all();
        foreach ($employees as $emp) {
            if ($emp->shift_id) {
                continue;
            }

            $pos = strtolower(trim($emp->position ?? ''));
            if (str_contains($pos, 'cs') || str_contains($pos, 'customer service')) {
                $targetId = $shiftCs?->id;
            } elseif (str_contains($pos, 'teknisi') || str_contains($pos, 'maintenance') || str_contains($pos, 'kolektor') || str_contains($pos, 'infra')) {
                $targetId = $shiftTeknisi?->id;
            } else {
                $targetId = $shiftAdmin?->id;
            }

            if ($targetId) {
                $emp->update(['shift_id' => $targetId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse
    }
};
