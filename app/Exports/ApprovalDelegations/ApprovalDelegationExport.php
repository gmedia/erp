<?php

namespace App\Exports\ApprovalDelegations;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ApprovalDelegationExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Builder $query
    ) {}

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Delegator',
            'Delegate',
            'Approvable Type',
            'Start Date',
            'End Date',
            'Reason',
            'Status',
            'Created At',
        ];
    }

    /**
     * @param  \App\Models\ApprovalDelegation  $row
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->delegator->name,
            $row->delegate->name,
            $row->approvable_type,
            $row->start_date->format('Y-m-d'),
            $row->end_date->format('Y-m-d'),
            $row->reason,
            $row->is_active ? 'Active' : 'Inactive',
            $row->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
