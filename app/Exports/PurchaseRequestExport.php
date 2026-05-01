<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class PurchaseRequestExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    public function __construct(
        private readonly array $filters = []
    ) {}

    public function query(): Builder
    {
        $query = PurchaseRequest::query()->with(['branch', 'department', 'requester']);

        $this->applyConfiguredFilters(
            $query,
            $this->filters,
            ['pr_number', 'notes', 'rejection_reason'],
            [
                'branch' => 'branch_id',
                'department' => 'department_id',
                'requested_by' => 'requested_by',
                'priority' => 'priority',
                'status' => 'status',
            ],
            [
                'request_date' => ['from' => 'request_date_from', 'to' => 'request_date_to'],
                'required_date' => ['from' => 'required_date_from', 'to' => 'required_date_to'],
            ],
            ['pr_number', 'request_date', 'required_date', 'priority', 'status', 'estimated_amount', 'created_at'],
        );

        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($purchaseRequest): array
    {
        return $this->mapExportRow($purchaseRequest, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (PurchaseRequest $pr): mixed => $pr->id,
            'PR Number' => fn (PurchaseRequest $pr): mixed => $pr->pr_number,
            'Branch' => fn (PurchaseRequest $pr): mixed => $this->relatedAttribute($pr, 'branch', 'name'),
            'Department' => fn (PurchaseRequest $pr): mixed => $this->relatedAttribute($pr, 'department', 'name'),
            'Requested By' => fn (PurchaseRequest $pr): mixed => $this->relatedAttribute($pr, 'requester', 'name'),
            'Request Date' => fn (PurchaseRequest $pr): mixed => $this->formatDateValue($pr->request_date, 'Y-m-d'),
            'Required Date' => fn (PurchaseRequest $pr): mixed => $this->formatDateValue($pr->required_date, 'Y-m-d'),
            'Priority' => fn (PurchaseRequest $pr): mixed => $pr->priority,
            'Status' => fn (PurchaseRequest $pr): mixed => $pr->status,
            'Estimated Amount' => fn (PurchaseRequest $pr): mixed => $pr->estimated_amount,
            'Notes' => fn (PurchaseRequest $pr): mixed => $pr->notes,
            'Created At' => fn (PurchaseRequest $pr): mixed => $this->formatIso8601($pr->created_at),
        ];
    }
}
