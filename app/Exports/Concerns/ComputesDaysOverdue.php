<?php

namespace App\Exports\Concerns;

use DateTimeInterface;
use Illuminate\Support\Carbon;

trait ComputesDaysOverdue
{
    /**
     * Cross-DB safe replacement for MariaDB DATEDIFF(today, due_date).
     * Optional $statusGate restricts non-zero result to specific statuses.
     *
     * @param  list<string>|null  $statusGate
     */
    protected function computeDaysOverdue(mixed $dueDate, ?string $status = null, ?array $statusGate = null): int
    {
        if ($statusGate !== null && ($status === null || ! in_array($status, $statusGate, true))) {
            return 0;
        }

        if ($dueDate === null) {
            return 0;
        }

        $due = $dueDate instanceof DateTimeInterface
            ? Carbon::instance($dueDate)
            : Carbon::parse((string) $dueDate);

        $today = Carbon::today();

        return $due->lt($today) ? $due->diffInDays($today) : 0;
    }
}
