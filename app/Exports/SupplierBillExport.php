<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Exports\Concerns\ExportsNumberFormats;
use App\Models\SupplierBill;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class SupplierBillExport extends BaseExport implements WithColumnFormatting
{
    use ExportsNumberFormats;

    protected array $defaultSortColumns = ['bill_date', 'bill_number'];

    public function query(): Builder
    {
        $query = SupplierBill::query()->with(['supplier', 'branch', 'department']);

        $this->applyConfiguredFilters(
            $query,
            $this->filters,
            ['bill_number', 'supplier_invoice_number', 'payment_terms', 'notes'],
            [
                'supplier' => 'supplier_id',
                'branch' => 'branch_id',
                'status' => 'status',
                'currency' => 'currency',
            ],
            [
                'bill_date' => ['from' => 'bill_date_from', 'to' => 'bill_date_to'],
                'due_date' => ['from' => 'due_date_from', 'to' => 'due_date_to'],
            ],
            [
                'bill_number',
                'bill_date',
                'due_date',
                'currency',
                'status',
                'grand_total',
                'amount_paid',
                'amount_due',
                'created_at',
            ]
        );

        return $query;
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
            'Department' => fn (SupplierBill $bill): mixed => $this->relatedAttribute($bill, 'department', 'name'),
            'Bill Date' => fn (SupplierBill $bill): mixed => $this->formatDateValue($bill->bill_date, 'Y-m-d'),
            'Due Date' => fn (SupplierBill $bill): mixed => $this->formatDateValue($bill->due_date, 'Y-m-d'),
            'Supplier Invoice #' => fn (SupplierBill $bill): mixed => $bill->supplier_invoice_number,
            'Payment Terms' => fn (SupplierBill $bill): mixed => $bill->payment_terms,
            'Currency' => fn (SupplierBill $bill): mixed => $bill->currency,
            'Status' => fn (SupplierBill $bill): mixed => $bill->status,
            'Subtotal' => fn (SupplierBill $bill): mixed => $this->numericValue($bill->subtotal),
            'Tax Amount' => fn (SupplierBill $bill): mixed => $this->numericValue($bill->tax_amount),
            'Discount Amount' => fn (SupplierBill $bill): mixed => $this->numericValue($bill->discount_amount),
            'Grand Total' => fn (SupplierBill $bill): mixed => $this->numericValue($bill->grand_total),
            'Amount Paid' => fn (SupplierBill $bill): mixed => $this->numericValue($bill->amount_paid),
            'Amount Due' => fn (SupplierBill $bill): mixed => $this->numericValue($bill->amount_due),
            'Notes' => fn (SupplierBill $bill): mixed => $bill->notes,
            'Created At' => fn (SupplierBill $bill): mixed => $this->formatIso8601($bill->created_at),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function currencyColumnHeadings(): array
    {
        return [
            'Subtotal',
            'Tax Amount',
            'Discount Amount',
            'Grand Total',
            'Amount Paid',
            'Amount Due',
        ];
    }
}
