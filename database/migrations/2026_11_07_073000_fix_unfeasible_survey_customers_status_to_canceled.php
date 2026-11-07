<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Temukan seluruh pelanggan yang batal/tidak layak di tahap survey tetapi keliru berstatus 'terminated'
        $unfeasibleSurveyCustomers = DB::table('customers')
            ->where('status', 'terminated')
            ->where(function ($query) {
                $query->where('cancel_stage', 'survey')
                    ->orWhere(function ($sub) {
                        $sub->whereNull('activation_date')
                            ->whereExists(function ($sq) {
                                $sq->select(DB::raw(1))
                                    ->from('surveys')
                                    ->whereColumn('surveys.customer_id', 'customers.id')
                                    ->where('feasibility', 'not_feasible');
                            });
                    });
            })
            ->get();

        foreach ($unfeasibleSurveyCustomers as $c) {
            DB::table('customers')
                ->where('id', $c->id)
                ->update([
                    'status' => 'canceled',
                    'cancel_stage' => 'survey',
                    'updated_at' => now(),
                ]);

            // Bersihkan deduction atau clawback pinalti jika ada yang sempat terbentuk keliru
            DB::table('deductions')
                ->where('reference_id', 'clawback_booking_cust_' . $c->id)
                ->orWhere('reference_id', 'clawback_survey_cust_' . $c->id)
                ->delete();

            DB::table('affiliate_commissions')
                ->where('customer_id', $c->id)
                ->whereIn('type', ['clawback_booking', 'clawback_survey'])
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert kembali jika di-rollback
        DB::table('customers')
            ->where('cancel_stage', 'survey')
            ->whereNull('activation_date')
            ->where('status', 'canceled')
            ->update([
                'status' => 'terminated',
                'updated_at' => now(),
            ]);
    }
};
