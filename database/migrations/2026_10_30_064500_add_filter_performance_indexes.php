<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function getIndexes(): array
    {
        return [
            'customers' => [
                'cust_status_area_idx' => ['status', 'area_id'],
                'cust_area_str_idx' => ['area'],
                'cust_package_status_idx' => ['package_id', 'status'],
                'cust_service_status_idx' => ['service_status', 'status'],
                'cust_sales_status_idx' => ['sales_id', 'status'],
                'cust_reg_date_idx' => ['registration_date'],
            ],
            'invoices' => [
                'inv_reseller_status_due_idx' => ['is_reseller_balance', 'status', 'due_date'],
                'inv_issued_date_idx' => ['issued_date'],
                'inv_customer_status_idx' => ['customer_id', 'status'],
            ],
            'payments' => [
                'pay_status_date_idx' => ['status', 'payment_date'],
                'pay_invoice_status_idx' => ['invoice_id', 'status'],
            ],
            'technician_schedules' => [
                'ts_type_tech_date_idx' => ['type', 'technician_id', 'scheduled_date'],
                'ts_cust_type_status_idx' => ['customer_id', 'type', 'status'],
            ],
            'surveys' => [
                'srv_cust_feasibility_idx' => ['customer_id', 'feasibility'],
                'srv_surveyor_idx' => ['surveyor_id'],
            ],
            'tickets' => [
                'tkt_status_prio_idx' => ['status', 'priority'],
            ],
            'pgk_tickets' => [
                'pgk_status_area_idx' => ['status', 'area_id'],
            ],
            'pgs_tickets' => [
                'pgs_status_area_idx' => ['status', 'area_id'],
            ],
        ];
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return !empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function up(): void
    {
        foreach ($this->getIndexes() as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach ($indexes as $indexName => $columns) {
                if (!Schema::hasColumns($table, $columns) || $this->indexExists($table, $indexName)) {
                    continue;
                }
                try {
                    Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                        $blueprint->index($columns, $indexName);
                    });
                } catch (\Throwable $e) {
                    // Skip safely if column type doesn't allow standard btree index
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->getIndexes() as $table => $indexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach (array_keys($indexes) as $indexName) {
                if (!$this->indexExists($table, $indexName)) {
                    continue;
                }
                try {
                    Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                        $blueprint->dropIndex($indexName);
                    });
                } catch (\Throwable $e) {
                }
            }
        }
    }
};

