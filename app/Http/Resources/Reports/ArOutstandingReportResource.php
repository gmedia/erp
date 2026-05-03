<?php

namespace App\Http\Resources\Reports;

use App\Http\Resources\Reports\Concerns\FormatsInvoiceReportData;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
 * @property int $days_overdue
 */
class ArOutstandingReportResource extends JsonResource
{
    use FormatsInvoiceReportData;

    public function toArray(Request $request): array
    {
        return array_merge($this->formatBaseInvoiceData(), [
            'days_overdue' => $this->days_overdue,
        ]);
    }
}
