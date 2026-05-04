<?php

namespace App\Http\Resources\Reports\Concerns;

use DateTimeInterface;

trait FormatsBillReportData
{
    protected function baseBillReportData(): array
    {
        return [
            'id' => $this->id,
            'bill' => [
                'number' => $this->bill_number,
                'supplier_invoice_number' => $this->supplier_invoice_number,
                'bill_date' => $this->formatReportDate($this->bill_date),
                'due_date' => $this->formatReportDate($this->due_date),
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
        ];
    }

    protected function formatReportDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return is_string($value) ? $value : null;
    }
}
