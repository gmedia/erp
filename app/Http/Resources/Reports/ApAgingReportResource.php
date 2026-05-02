<?php

namespace App\Http\Resources\Reports;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $bill_number
 * @property string|null $supplier_invoice_number
 * @property DateTimeInterface|string|null $bill_date
 * @property DateTimeInterface|string|null $due_date
 * @property numeric-string|int|float $grand_total
 * @property numeric-string|int|float $amount_paid
 * @property numeric-string|int|float $amount_due
 * @property string $status
 * @property string $currency
 * @property string|null $payment_terms
 * @property string|null $notes
 * @property int $supplier_id
 * @property string $supplier_name
 * @property int $branch_id
 * @property string $branch_name
 * @property string|null $purchase_order_number
 * @property string|null $goods_receipt_number
 * @property numeric-string|int|float $current_amount
 * @property numeric-string|int|float $days_1_30
 * @property numeric-string|int|float $days_31_60
 * @property numeric-string|int|float $days_61_90
 * @property numeric-string|int|float $days_over_90
 */
class ApAgingReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bill' => [
                'number' => $this->bill_number,
                'supplier_invoice_number' => $this->supplier_invoice_number,
                'bill_date' => $this->formatDate($this->bill_date),
                'due_date' => $this->formatDate($this->due_date),
                'status' => $this->status,
                'currency' => $this->currency,
                'payment_terms' => $this->payment_terms,
                'notes' => $this->notes,
            ],
            'supplier' => [
                'id' => $this->supplier_id,
                'name' => $this->supplier_name,
            ],
            'branch' => [
                'id' => $this->branch_id,
                'name' => $this->branch_name,
            ],
            'references' => [
                'purchase_order_number' => $this->purchase_order_number,
                'goods_receipt_number' => $this->goods_receipt_number,
            ],
            'amounts' => [
                'grand_total' => (string) $this->grand_total,
                'amount_paid' => (string) $this->amount_paid,
                'amount_due' => (string) $this->amount_due,
            ],
            'aging_buckets' => [
                'current' => (string) $this->current_amount,
                'days_1_30' => (string) $this->days_1_30,
                'days_31_60' => (string) $this->days_31_60,
                'days_61_90' => (string) $this->days_61_90,
                'days_over_90' => (string) $this->days_over_90,
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
