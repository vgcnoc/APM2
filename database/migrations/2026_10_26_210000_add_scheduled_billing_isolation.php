<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dateTime('isolation_at')->nullable()->index();
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dateTime('billing_isolated_at')->nullable();
            $table->boolean('billing_isolation_pending')->default(false);
        });
        if (Schema::hasTable('settings') && !DB::table('settings')->where('key', 'isolate_day')->exists()) {
            $settings = DB::table('settings')->whereIn('key', ['invoice_issue_date', 'isolate_days'])->pluck('value', 'key');
            $reference = \Carbon\CarbonImmutable::now(config('billing.timezone', 'Asia/Jakarta'))->startOfMonth()
                ->day(max(1, min(28, (int) ($settings['invoice_issue_date'] ?? 1))))
                ->addDays(max(0, (int) ($settings['isolate_days'] ?? 3)));
            DB::table('settings')->insert(['key' => 'isolate_day', 'value' => (string) $reference->day, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['isolation_at']);
            $table->dropColumn('isolation_at');
        });
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn(['billing_isolated_at', 'billing_isolation_pending']));
    }
};
