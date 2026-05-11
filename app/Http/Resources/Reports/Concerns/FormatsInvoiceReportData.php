<?php

namespace App\Http\Resources\Reports\Concerns;

use DateTimeInterface;

trait FormatsInvoiceReportData
{
    protected function formatBaseInvoiceData(): array
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
