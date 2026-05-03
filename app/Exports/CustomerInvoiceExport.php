<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\CustomerInvoice;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class CustomerInvoiceExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    public function __construct(
        private readonly array $filters = []
    ) {}

    public function query(): Builder
    {
        $query = CustomerInvoice::query()->with(['customer', 'branch', 'fiscalYear']);

        $this->applyConfiguredFilters($query, $this->filters, ['invoice_number', 'payment_terms', 'notes'], [
            'customer' => 'customer_id',
            'branch' => 'branch_id',
            'fiscal_year' => 'fiscal_year_id',
            'status' => 'status',
            'currency' => 'currency',
        ], [
            'invoice_date' => ['from' => 'invoice_date_from', 'to' => 'invoice_date_to'],
            'due_date' => ['from' => 'due_date_from', 'to' => 'due_date_to'],
        ], [
            'invoice_number',
            'invoice_date',
            'due_date',
            'currency',
            'status',
            'grand_total',
            'amount_due',
            'created_at',
        ]);

        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($customerInvoice): array
    {
        return $this->mapExportRow($customerInvoice, $this->columns());
    }

    protected function columns(): array
    {
        return [
            'ID' => fn (CustomerInvoice $invoice): mixed => $invoice->id,
            'Invoice Number' => fn (CustomerInvoice $invoice): mixed => $invoice->invoice_number,
            'Customer' => fn (CustomerInvoice $invoice): mixed => $this->relatedAttribute($invoice, 'customer', 'name'),
            'Branch' => fn (CustomerInvoice $invoice): mixed => $this->relatedAttribute($invoice, 'branch', 'name'),
            'Fiscal Year' => fn (CustomerInvoice $invoice): mixed => $this->relatedAttribute($invoice, 'fiscalYear', 'name'),
            'Invoice Date' => fn (CustomerInvoice $invoice): mixed => $this->formatDateValue($invoice->invoice_date, 'Y-m-d'),
            'Due Date' => fn (CustomerInvoice $invoice): mixed => $this->formatDateValue($invoice->due_date, 'Y-m-d'),
            'Payment Terms' => fn (CustomerInvoice $invoice): mixed => $invoice->payment_terms,
            'Currency' => fn (CustomerInvoice $invoice): mixed => $invoice->currency,
            'Status' => fn (CustomerInvoice $invoice): mixed => $invoice->status,
            'Subtotal' => fn (CustomerInvoice $invoice): mixed => $invoice->subtotal,
            'Tax Amount' => fn (CustomerInvoice $invoice): mixed => $invoice->tax_amount,
            'Discount Amount' => fn (CustomerInvoice $invoice): mixed => $invoice->discount_amount,
            'Grand Total' => fn (CustomerInvoice $invoice): mixed => $invoice->grand_total,
            'Amount Received' => fn (CustomerInvoice $invoice): mixed => $invoice->amount_received,
            'Credit Note Amount' => fn (CustomerInvoice $invoice): mixed => $invoice->credit_note_amount,
            'Amount Due' => fn (CustomerInvoice $invoice): mixed => $invoice->amount_due,
            'Notes' => fn (CustomerInvoice $invoice): mixed => $invoice->notes,
            'Created At' => fn (CustomerInvoice $invoice): mixed => $this->formatIso8601($invoice->created_at),
        ];
    }
}
