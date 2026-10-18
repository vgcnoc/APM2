<?php

namespace App\Traits;

use App\Models\User;
use App\Services\DataScopeService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait untuk mempermudah model menerapkan data scoping (own, area, all)
 */
trait HasDataScope
{
    /**
     * Scope query berdasarkan hak akses data user (own, per area, all area)
     */
    public function scopeForUserScope(Builder $query, ?User $user = null, ?string $module = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query;
        }

        $modelClass = static::class;

        return match ($modelClass) {
            \App\Models\Customer::class => DataScopeService::applyCustomerScope($query, $user, $module ?? 'customers_all'),
            \App\Models\Ticket::class => DataScopeService::applyTicketScope($query, $user),
            \App\Models\PgsTicket::class => DataScopeService::applyPgsScope($query, $user),
            \App\Models\PgkTicket::class => DataScopeService::applyPgkScope($query, $user),
            \App\Models\Invoice::class => DataScopeService::applyInvoiceScope($query, $user),
            \App\Models\MaterialTransaction::class => DataScopeService::applyMaterialTransactionScope($query, $user),
            \App\Models\Attendance::class => DataScopeService::applyAttendanceScope($query, $user),
            default => DataScopeService::applyGeneralScope($query, $user, $module ?? 'general'),
        };
    }
}
