<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\ApPayment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class ApPaymentExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    public function __construct(
        private readonly array $filters = []
    ) {}

    public function query(): Builder
    {
        $query = ApPayment::query()->with(['supplier', 'branch', 'bankAccount']);

        $this->applyConfiguredFilters($query, $this->filters, ['payment_number', 'reference', 'notes'], [
            'supplier' => 'supplier_id',
            'branch' => 'branch_id',
            'status' => 'status',
            'payment_method' => 'payment_method',
            'currency' => 'currency',
        ], [
            'payment_date' => ['from' => 'payment_date_from', 'to' => 'payment_date_to'],
        ], [
            'payment_number',
            'payment_date',
            'payment_method',
            'currency',
            'status',
            'total_amount',
            'total_allocated',
            'created_at',
        ]);

        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($apPayment): array
    {
        return $this->mapExportRow($apPayment, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (ApPayment $payment): mixed => $payment->id,
            'Payment Number' => fn (ApPayment $payment): mixed => $payment->payment_number,
            'Supplier' => fn (ApPayment $payment): mixed => $this->relatedAttribute($payment, 'supplier', 'name'),
            'Branch' => fn (ApPayment $payment): mixed => $this->relatedAttribute($payment, 'branch', 'name'),
            'Bank Account' => fn (ApPayment $payment): mixed => $this->relatedAttribute($payment, 'bankAccount', 'name'),
            'Payment Date' => fn (ApPayment $payment): mixed => $this->formatDateValue($payment->payment_date, 'Y-m-d'),
            'Payment Method' => fn (ApPayment $payment): mixed => $payment->payment_method,
            'Currency' => fn (ApPayment $payment): mixed => $payment->currency,
            'Status' => fn (ApPayment $payment): mixed => $payment->status,
            'Total Amount' => fn (ApPayment $payment): mixed => $payment->total_amount,
            'Total Allocated' => fn (ApPayment $payment): mixed => $payment->total_allocated,
            'Total Unallocated' => fn (ApPayment $payment): mixed => $payment->total_unallocated,
            'Reference' => fn (ApPayment $payment): mixed => $payment->reference,
            'Notes' => fn (ApPayment $payment): mixed => $payment->notes,
            'Created At' => fn (ApPayment $payment): mixed => $this->formatIso8601($payment->created_at),
        ];
    }
}
