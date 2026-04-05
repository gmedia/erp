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
            [
                'pr_number',
                'request_date',
                'required_date',
                'priority',
                'status',
                'estimated_amount',
                'created_at',
            ],
        );

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'PR Number',
            'Branch',
            'Department',
            'Requested By',
            'Request Date',
            'Required Date',
            'Priority',
            'Status',
            'Estimated Amount',
            'Notes',
            'Created At',
        ];
    }

    public function map($purchaseRequest): array
    {
        return [
            $purchaseRequest->id,
            $purchaseRequest->pr_number,
            $purchaseRequest->branch?->name,
            $purchaseRequest->department?->name,
            $purchaseRequest->requester?->name,
            $purchaseRequest->request_date?->format('Y-m-d'),
            $purchaseRequest->required_date?->format('Y-m-d'),
            $purchaseRequest->priority,
            $purchaseRequest->status,
            $purchaseRequest->estimated_amount,
            $purchaseRequest->notes,
            $purchaseRequest->created_at?->toIso8601String(),
        ];
    }
}
