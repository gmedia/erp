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

    protected array $filters;

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
        return $this->exportHeadings($this->columns());
    }

    public function map($movement): array
    {
        return $this->mapExportRow($movement, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (AssetMovement $m): mixed => $m->id,
            'Asset Code' => fn (AssetMovement $m): mixed => $m->asset->asset_code,
            'Asset Name' => fn (AssetMovement $m): mixed => $m->asset->name,
            'Type' => fn (AssetMovement $m): mixed => $m->movement_type,
            'Date' => fn (AssetMovement $m): mixed => $this->formatDateValue($m->moved_at, 'Y-m-d H:i:s'),
            'Origin Branch' => fn (AssetMovement $m): mixed => $this->relatedAttribute($m, 'fromBranch', 'name'),
            'Destination Branch' => fn (AssetMovement $m): mixed => $this->relatedAttribute($m, 'toBranch', 'name'),
            'Origin Location' => fn (AssetMovement $m): mixed => $this->relatedAttribute($m, 'fromLocation', 'name'),
            'Destination Location' => fn (AssetMovement $m): mixed => $this->relatedAttribute($m, 'toLocation', 'name'),
            'Origin Department' => fn (AssetMovement $m): mixed => $this->relatedAttribute($m, 'fromDepartment', 'name'),
            'Destination Department' => fn (AssetMovement $m): mixed => $this->relatedAttribute($m, 'toDepartment', 'name'),
            'Origin Employee' => fn (AssetMovement $m): mixed => $this->relatedAttribute($m, 'fromEmployee', 'name'),
            'Destination Employee' => fn (AssetMovement $m): mixed => $this->relatedAttribute($m, 'toEmployee', 'name'),
            'Reference' => fn (AssetMovement $m): mixed => $m->reference,
            'Notes' => fn (AssetMovement $m): mixed => $m->notes,
            'Recorded By' => fn (AssetMovement $m): mixed => $this->relatedAttribute($m, 'createdBy', 'name'),
            'Created At' => fn (AssetMovement $m): mixed => $this->formatIso8601($m->created_at),
        ];
    }
}
