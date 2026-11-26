<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Exports\Concerns\ExportsNumberFormats;
use App\Models\ArReceipt;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class ArReceiptExport extends BaseExport implements WithColumnFormatting
{
    use ExportsNumberFormats;

    protected array $defaultSortColumns = ['receipt_date', 'receipt_number'];

    public function query(): Builder
    {
        $query = ArReceipt::query()->with(['customer', 'branch', 'fiscalYear', 'bankAccount']);

        $this->applyConfiguredFilters($query, $this->filters, ['receipt_number', 'reference', 'notes'], [
            'customer' => 'customer_id',
            'branch' => 'branch_id',
            'fiscal_year' => 'fiscal_year_id',
            'bank_account' => 'bank_account_id',
            'status' => 'status',
            'payment_method' => 'payment_method',
            'currency' => 'currency',
        ], [
            'receipt_date' => ['from' => 'receipt_date_from', 'to' => 'receipt_date_to'],
        ], [
            'receipt_number',
            'receipt_date',
            'payment_method',
            'currency',
            'status',
            'total_amount',
            'created_at',
        ]);

        return $query;
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (ArReceipt $receipt): mixed => $receipt->id,
            'Receipt Number' => fn (ArReceipt $receipt): mixed => $receipt->receipt_number,
            'Customer' => fn (ArReceipt $receipt): mixed => $this->relatedAttribute($receipt, 'customer', 'name'),
            'Branch' => fn (ArReceipt $receipt): mixed => $this->relatedAttribute($receipt, 'branch', 'name'),
            'Fiscal Year' => fn (ArReceipt $receipt): mixed => $this->relatedAttribute($receipt, 'fiscalYear', 'name'),
            'Receipt Date' => fn (ArReceipt $receipt): mixed => $this->formatDateValue($receipt->receipt_date, 'Y-m-d'),
            'Payment Method' => fn (ArReceipt $receipt): mixed => $receipt->payment_method,
            'Bank Account' => fn (ArReceipt $receipt): mixed => $this->relatedAttribute(
                $receipt,
                'bankAccount',
                'name'
            ),
            'Currency' => fn (ArReceipt $receipt): mixed => $receipt->currency,
            'Status' => fn (ArReceipt $receipt): mixed => $receipt->status,
            'Total Amount' => fn (ArReceipt $receipt): mixed => $this->numericValue($receipt->total_amount),
            'Total Allocated' => fn (ArReceipt $receipt): mixed => $this->numericValue($receipt->total_allocated),
            'Total Unallocated' => fn (ArReceipt $receipt): mixed => $this->numericValue($receipt->total_unallocated),
            'Reference' => fn (ArReceipt $receipt): mixed => $receipt->reference,
            'Notes' => fn (ArReceipt $receipt): mixed => $receipt->notes,
            'Created At' => fn (ArReceipt $receipt): mixed => $this->formatIso8601($receipt->created_at),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function currencyColumnHeadings(): array
    {
        return ['Total Amount', 'Total Allocated', 'Total Unallocated'];
    }
}
