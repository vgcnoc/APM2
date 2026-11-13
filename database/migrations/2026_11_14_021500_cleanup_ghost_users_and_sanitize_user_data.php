<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Customer;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Temukan user dummy/ghost yang namanya '-' atau email '@isp.local'
        $ghostUsers = User::where('name', '-')
            ->orWhere('email', '@isp.local')
            ->orWhere('email', 'like', '@%')
            ->get();

        foreach ($ghostUsers as $gu) {
            // Lepaskan relasi pelanggan direct
            Customer::where('sales_id', $gu->id)->update(['sales_id' => null]);
            Customer::where('user_id', $gu->id)->update(['user_id' => null]);

            // Lepaskan Spatie role & permissions
            DB::table('model_has_roles')->where('model_id', $gu->id)->where('model_type', User::class)->delete();
            DB::table('model_has_permissions')->where('model_id', $gu->id)->where('model_type', User::class)->delete();

            // Hapus ghost user
            $gu->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed for cleaning up ghost data
    }
};

