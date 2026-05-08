<?php

namespace App\Http\Resources\Reports;

use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $payment_number
 * @property DateTimeInterface|string|null $payment_date
 * @property string $payment_method
 * @property string $currency
 * @property numeric-string|int|float $total_amount
 * @property numeric-string|int|float $total_allocated
 * @property numeric-string|int|float $total_unallocated
 * @property string|null $reference
 * @property string $status
 * @property string|null $notes
 * @property int $supplier_id
 * @property string $supplier_name
 * @property int $branch_id
 * @property string $branch_name
 * @property int|null $bank_account_id
 * @property string|null $bank_account_name
 * @property string|null $bank_account_number
 */
class ApPaymentHistoryReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment' => [
                'number' => $this->payment_number,
                'date' => $this->formatDate($this->payment_date),
                'method' => $this->payment_method,
                'currency' => $this->currency,
                'status' => $this->status,
                'reference' => $this->reference,
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
            'bank_account' => $this->bank_account_id ? [
                'id' => $this->bank_account_id,
                'name' => $this->bank_account_name,
                'account_number' => $this->bank_account_number,
            ] : null,
            'amounts' => [
                'total' => (string) $this->total_amount,
                'allocated' => (string) $this->total_allocated,
                'unallocated' => (string) $this->total_unallocated,
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
