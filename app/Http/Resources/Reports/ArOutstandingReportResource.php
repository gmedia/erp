<?php

namespace App\Http\Resources\Reports;

use App\Http\Resources\Reports\Concerns\FormatsInvoiceReportData;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property int $customer_invoice_id
 * @property string $invoice_number
 * @property DateTimeInterface|string|null $invoice_date
 * @property DateTimeInterface|string|null $due_date
 * @property numeric-string|int|float $grand_total
 * @property numeric-string|int|float $amount_received
 * @property numeric-string|int|float $credit_note_amount
 * @property numeric-string|int|float $amount_due
 * @property string $status
 * @property int $customer_id
 * @property string $customer_name
 * @property int $branch_id
 * @property string $branch_name
 */
class ArOutstandingReportResource extends JsonResource
{
    use FormatsInvoiceReportData;

    private const OVERDUE_STATUSES = ['sent', 'partially_paid', 'overdue'];

    public function toArray(Request $request): array
    {
        return array_merge($this->formatBaseInvoiceData(), [
            'days_overdue' => $this->computeDaysOverdue(),
        ]);
    }

    private function computeDaysOverdue(): int
    {
        if (! in_array($this->status, self::OVERDUE_STATUSES, true)) {
            return 0;
        }

        $dueDate = $this->due_date;

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
