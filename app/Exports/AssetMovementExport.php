<?php

namespace App\Exports;

use App\Domain\AssetMovements\AssetMovementFilterService;
use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\AssetMovement;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class AssetMovementExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = AssetMovement::query()->with([
            'asset' => fn ($q) => $q->withTrashed(),
            'fromBranch', 'toBranch', 'fromLocation', 'toLocation',
            'fromDepartment', 'toDepartment', 'fromEmployee', 'toEmployee', 'createdBy',
        ]);

        $filterService = app(AssetMovementFilterService::class);

        if (! empty($this->filters['search'])) {
            $filterService->applySearch($query, $this->filters['search'], ['reference', 'notes']);
        } else {
            $filterService->applyAdvancedFilters($query, $this->filters);
        }

        $filterService->applySorting(
            $query,
            $this->filters['sort_by'] ?? 'moved_at',
            $this->normalizeSortDirection($this->filters),
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
            'Reference', 'Notes', 'Recorded By', 'Created At',
        ];
    }

    public function map($movement): array
    {
        return [
            $movement->id,
            $movement->asset?->asset_code,
            $movement->asset?->name,
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
}
