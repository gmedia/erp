<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\SupplierBill;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class SupplierBillExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    public function __construct(
        private readonly array $filters = []
    ) {}

    public function query(): Builder
    {
        $query = SupplierBill::query()->with(['supplier', 'branch']);

        $this->applyConfiguredFilters($query, $this->filters, ['bill_number', 'supplier_invoice_number', 'payment_terms', 'notes'], [
            'supplier' => 'supplier_id',
            'branch' => 'branch_id',
            'status' => 'status',
            'currency' => 'currency',
        ], [
            'bill_date' => ['from' => 'bill_date_from', 'to' => 'bill_date_to'],
            'due_date' => ['from' => 'due_date_from', 'to' => 'due_date_to'],
        ], [
            'bill_number',
            'bill_date',
            'due_date',
            'currency',
            'status',
            'grand_total',
            'amount_paid',
            'amount_due',
            'created_at',
        ]);

        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($supplierBill): array
    {
        return $this->mapExportRow($supplierBill, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (SupplierBill $bill): mixed => $bill->id,
            'Bill Number' => fn (SupplierBill $bill): mixed => $bill->bill_number,
            'Supplier' => fn (SupplierBill $bill): mixed => $this->relatedAttribute($bill, 'supplier', 'name'),
            'Branch' => fn (SupplierBill $bill): mixed => $this->relatedAttribute($bill, 'branch', 'name'),
            'Bill Date' => fn (SupplierBill $bill): mixed => $this->formatDateValue($bill->bill_date, 'Y-m-d'),
            'Due Date' => fn (SupplierBill $bill): mixed => $this->formatDateValue($bill->due_date, 'Y-m-d'),
            'Supplier Invoice #' => fn (SupplierBill $bill): mixed => $bill->supplier_invoice_number,
            'Payment Terms' => fn (SupplierBill $bill): mixed => $bill->payment_terms,
            'Currency' => fn (SupplierBill $bill): mixed => $bill->currency,
            'Status' => fn (SupplierBill $bill): mixed => $bill->status,
            'Subtotal' => fn (SupplierBill $bill): mixed => $bill->subtotal,
            'Tax Amount' => fn (SupplierBill $bill): mixed => $bill->tax_amount,
            'Discount Amount' => fn (SupplierBill $bill): mixed => $bill->discount_amount,
            'Grand Total' => fn (SupplierBill $bill): mixed => $bill->grand_total,
            'Amount Paid' => fn (SupplierBill $bill): mixed => $bill->amount_paid,
            'Amount Due' => fn (SupplierBill $bill): mixed => $bill->amount_due,
            'Notes' => fn (SupplierBill $bill): mixed => $bill->notes,
            'Created At' => fn (SupplierBill $bill): mixed => $this->formatIso8601($bill->created_at),
        ];
    }
}
