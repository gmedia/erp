<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\CreditNote;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class CreditNoteExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    public function __construct(
        private readonly array $filters = []
    ) {}

    public function query(): Builder
    {
        $query = CreditNote::query()->with(['customer', 'customerInvoice', 'branch', 'fiscalYear']);

        $this->applyConfiguredFilters($query, $this->filters, ['credit_note_number', 'notes'], [
            'customer' => 'customer_id',
            'customer_invoice' => 'customer_invoice_id',
            'branch' => 'branch_id',
            'fiscal_year' => 'fiscal_year_id',
            'reason' => 'reason',
            'status' => 'status',
        ], [
            'credit_note_date' => ['from' => 'credit_note_date_from', 'to' => 'credit_note_date_to'],
        ], [
            'credit_note_number',
            'credit_note_date',
            'reason',
            'status',
            'grand_total',
            'created_at',
        ]);

        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($creditNote): array
    {
        return $this->mapExportRow($creditNote, $this->columns());
    }

    protected function columns(): array
    {
        return [
            'ID' => fn (CreditNote $cn): mixed => $cn->id,
            'Credit Note Number' => fn (CreditNote $cn): mixed => $cn->credit_note_number,
            'Customer' => fn (CreditNote $cn): mixed => $this->relatedAttribute($cn, 'customer', 'name'),
            'Invoice Number' => fn (CreditNote $cn): mixed => $this->relatedAttribute($cn, 'customerInvoice', 'invoice_number'),
            'Branch' => fn (CreditNote $cn): mixed => $this->relatedAttribute($cn, 'branch', 'name'),
            'Fiscal Year' => fn (CreditNote $cn): mixed => $this->relatedAttribute($cn, 'fiscalYear', 'name'),
            'Credit Note Date' => fn (CreditNote $cn): mixed => $this->formatDateValue($cn->credit_note_date, 'Y-m-d'),
            'Reason' => fn (CreditNote $cn): mixed => $cn->reason,
            'Status' => fn (CreditNote $cn): mixed => $cn->status,
            'Subtotal' => fn (CreditNote $cn): mixed => $cn->subtotal,
            'Tax Amount' => fn (CreditNote $cn): mixed => $cn->tax_amount,
            'Grand Total' => fn (CreditNote $cn): mixed => $cn->grand_total,
            'Notes' => fn (CreditNote $cn): mixed => $cn->notes,
            'Created At' => fn (CreditNote $cn): mixed => $this->formatIso8601($cn->created_at),
        ];
    }
}
