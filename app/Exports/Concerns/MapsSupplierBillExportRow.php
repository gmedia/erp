<?php

namespace App\Exports\Concerns;

trait MapsSupplierBillExportRow
{
    /**
     * @return array<int, mixed>
     */
    protected function baseBillExportRow(mixed $row): array
    {
        return [
            $row->bill_number ?? '-',
            $row->supplier_invoice_number ?? '-',
            $row->supplier_name ?? '-',
            $row->branch_name ?? '-',
            $row->bill_date?->format('Y-m-d') ?? '-',
            $row->due_date?->format('Y-m-d') ?? '-',
            $row->grand_total ?? 0,
            $row->amount_paid ?? 0,
            $row->amount_due ?? 0,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected function billExportTrailingColumns(mixed $row): array
    {
        return [
            $row->status ?? '-',
            $row->currency ?? '-',
            $row->payment_terms ?? '-',
            $row->purchase_order_number ?? '-',
            $row->goods_receipt_number ?? '-',
            $row->notes ?? '-',
        ];
    }
}
