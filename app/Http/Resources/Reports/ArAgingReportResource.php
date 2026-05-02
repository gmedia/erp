<?php

namespace App\Http\Resources\Reports;

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
 * @property numeric-string|int|float $aging_current
 * @property numeric-string|int|float $aging_1_30
 * @property numeric-string|int|float $aging_31_60
 * @property numeric-string|int|float $aging_61_90
 * @property numeric-string|int|float $aging_over_90
 */
class ArAgingReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'customer_invoice' => [
                'id' => $this->customer_invoice_id,
                'invoice_number' => $this->invoice_number,
                'invoice_date' => $this->formatDate($this->invoice_date),
                'due_date' => $this->formatDate($this->due_date),
                'status' => $this->status,
            ],
            'customer' => [
                'id' => $this->customer_id,
                'name' => $this->customer_name,
            ],
            'branch' => [
                'id' => $this->branch_id,
                'name' => $this->branch_name,
            ],
            'amounts' => [
                'grand_total' => (string) $this->grand_total,
                'amount_received' => (string) $this->amount_received,
                'credit_note_amount' => (string) $this->credit_note_amount,
                'amount_due' => (string) $this->amount_due,
            ],
            'aging_buckets' => [
                'current' => (string) $this->aging_current,
                '1_30' => (string) $this->aging_1_30,
                '31_60' => (string) $this->aging_31_60,
                '61_90' => (string) $this->aging_61_90,
                'over_90' => (string) $this->aging_over_90,
            ],
        ];
    }

    private function formatDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return is_string($value) ? $value : null;
    }
}
