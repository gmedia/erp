<?php

namespace App\Exports;

use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseRequestExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly array $filters = []
    ) {
    }

    public function query(): Builder
    {
        $query = PurchaseRequest::query()->with(['branch', 'department', 'requester']);

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('pr_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('rejection_reason', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['branch'])) {
            $query->where('branch_id', $this->filters['branch']);
        }
        if (! empty($this->filters['department'])) {
            $query->where('department_id', $this->filters['department']);
        }
        if (! empty($this->filters['requested_by'])) {
            $query->where('requested_by', $this->filters['requested_by']);
        }
        if (! empty($this->filters['priority'])) {
            $query->where('priority', $this->filters['priority']);
        }
        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }
        if (! empty($this->filters['request_date_from'])) {
            $query->whereDate('request_date', '>=', $this->filters['request_date_from']);
        }
        if (! empty($this->filters['request_date_to'])) {
            $query->whereDate('request_date', '<=', $this->filters['request_date_to']);
        }
        if (! empty($this->filters['required_date_from'])) {
            $query->whereDate('required_date', '>=', $this->filters['required_date_from']);
        }
        if (! empty($this->filters['required_date_to'])) {
            $query->whereDate('required_date', '<=', $this->filters['required_date_to']);
        }

        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = strtolower($this->filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['pr_number', 'request_date', 'required_date', 'priority', 'status', 'estimated_amount', 'created_at'], true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

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

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
