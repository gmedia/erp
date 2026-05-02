<?php

namespace App\Http\Resources\Reports;

use App\Http\Resources\Reports\Concerns\FormatsBillReportData;
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
 * @property int $days_overdue
 */
class ApOutstandingReportResource extends JsonResource
{
    use FormatsBillReportData;

    public function toArray(Request $request): array
    {
        return array_merge($this->baseBillReportData(), [
            'days_overdue' => $this->days_overdue,
        ]);
    }
}
