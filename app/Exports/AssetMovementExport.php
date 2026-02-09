<?php

namespace App\Exports;

use App\Models\AssetMovement;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetMovementExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = AssetMovement::query()->with([
            'asset', 'fromBranch', 'toBranch', 'fromLocation', 'toLocation',
            'fromDepartment', 'toDepartment', 'fromEmployee', 'toEmployee', 'createdBy'
        ]);

        $filterService = app(\App\Domain\AssetMovements\AssetMovementFilterService::class);

        if (!empty($this->filters['search'])) {
            $filterService->applySearch($query, $this->filters['search'], ['reference', 'notes']);
        } else {
            $filterService->applyAdvancedFilters($query, $this->filters);
        }

        $filterService->applySorting(
            $query,
            $this->filters['sort_by'] ?? 'moved_at',
            $this->filters['sort_direction'] ?? 'desc',
            ['id', 'movement_type', 'moved_at', 'created_at']
        );

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID', 'Asset Code', 'Asset Name', 'Type', 'Date',
            'Origin Branch', 'Destination Branch',
            'Origin Location', 'Destination Location',
            'Origin Department', 'Destination Department',
            'Origin Employee', 'Destination Employee',
            'Reference', 'Notes', 'Recorded By', 'Created At'
        ];
    }

    public function map($movement): array
    {
        return [
            $movement->id,
            $movement->asset->asset_code,
            $movement->asset->name,
            $movement->movement_type,
            $movement->moved_at?->format('Y-m-d H:i:s'),
            $movement->fromBranch?->name,
            $movement->toBranch?->name,
            $movement->fromLocation?->name,
            $movement->toLocation?->name,
            $movement->fromDepartment?->name,
            $movement->toDepartment?->name,
            $movement->fromEmployee?->name,
            $movement->toEmployee?->name,
            $movement->reference,
            $movement->notes,
            $movement->createdBy?->name,
            $movement->created_at?->toIso8601String(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
