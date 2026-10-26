<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class BillingIsolationSchedule
{
    public function settings(): array
    {
        $settings = Setting::whereIn('key', ['invoice_issue_date', 'isolate_day', 'isolate_days', 'isolate_time'])->pluck('value', 'key');
        return [
            'isolate_day' => isset($settings['isolate_day']) ? (int) $settings['isolate_day'] : null,
            'isolate_days' => max(0, (int) ($settings['isolate_days'] ?? 3)),
            'isolate_time' => $settings['isolate_time'] ?? '00:00',
            'invoice_issue_date' => max(1, min(28, (int) ($settings['invoice_issue_date'] ?? 1))),
        ];
    }

    public function timezone(): string { return config('billing.timezone', 'Asia/Jakarta'); }

    /** Returns an instant in the app/database timezone; the issue date is a billing calendar date. */
    public function forIssueDate(CarbonInterface|string $issuedDate, ?array $settings = null): CarbonImmutable
    {
        $settings ??= $this->settings();
        $date = $issuedDate instanceof CarbonInterface ? $issuedDate->format('Y-m-d') : substr($issuedDate, 0, 10);
        $issued = CarbonImmutable::parse($date, $this->timezone())->startOfDay();
        $day = $settings['isolate_day'] ?? null;
        if ($day !== null) {
            $day = max(1, min(31, (int) $day));
            $month = $issued->startOfMonth();
            $target = $month->day(min($day, $month->daysInMonth));
            if ($target->lt($issued)) {
                $month = $month->addMonthNoOverflow();
                $target = $month->day(min($day, $month->daysInMonth));
            }
        } else {
            // Preserve old installations until the new monthly date is explicitly saved.
            $target = $issued->addDays(max(0, (int) ($settings['isolate_days'] ?? 3)));
        }
        $time = preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $settings['isolate_time'] ?? '', $parts) ? [$parts[1], $parts[2]] : [0, 0];
        return $target->setTime((int) $time[0], (int) $time[1], 0)->setTimezone(config('app.timezone', 'UTC'));
    }

    public function displayDay(?array $settings = null): int
    {
        $settings ??= $this->settings();
        return $settings['isolate_day'] ?? $this->forIssueDate(now()->startOfMonth()->day($settings['invoice_issue_date']), $settings)->setTimezone($this->timezone())->day;
    }

    public function deadline(Invoice $invoice, ?array $settings = null): CarbonImmutable
    {
        return $invoice->isolation_at ? CarbonImmutable::instance($invoice->isolation_at)
            : $this->forIssueDate($invoice->issued_date, $settings);
    }

    /** Recalculate outstanding invoices only; paid/cancelled/top-up records stay historical. */
    public function refreshOutstanding(): int
    {
        $settings = $this->settings();
        $count = 0;
        Invoice::whereIn('status', ['unpaid', 'partial', 'overdue'])->where('is_reseller_balance', false)
            ->whereNotNull('issued_date')->chunkById(200, function ($invoices) use ($settings, &$count) {
                foreach ($invoices as $invoice) {
                    $invoice->forceFill(['isolation_at' => $this->forIssueDate($invoice->issued_date, $settings)])->saveQuietly();
                    $count++;
                }
            });
        return $count;
    }
}
