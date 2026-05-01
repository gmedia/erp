<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\AssetLocation;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class AssetLocationExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = AssetLocation::query()->with(['branch', 'parent']);

        $this->applySearchFilter($query, $this->filters, ['code', 'name']);
        $this->applyExactFilters($query, $this->filters, [
            'branch_id' => 'branch_id',
            'parent_id' => 'parent_id',
        ]);

        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->normalizeSortDirection($this->filters);
        $allowedSortColumns = ['code', 'name', 'branch_id', 'parent_id', 'created_at', 'updated_at'];

        if ($sortBy === 'branch') {
            $query
                ->leftJoin('branches', 'asset_locations.branch_id', '=', 'branches.id')
                ->select('asset_locations.*')
                ->orderBy('branches.name', $sortDirection);
        } elseif ($sortBy === 'parent') {
            $query
                ->leftJoin('asset_locations as parents', 'asset_locations.parent_id', '=', 'parents.id')
                ->select('asset_locations.*')
                ->orderBy('parents.name', $sortDirection);
        } elseif (in_array($sortBy, $allowedSortColumns, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($assetLocation): array
    {
        return $this->mapExportRow($assetLocation, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (AssetLocation $l): mixed => $l->id,
            'Code' => fn (AssetLocation $l): mixed => $l->code,
            'Name' => fn (AssetLocation $l): mixed => $l->name,
            'Branch' => fn (AssetLocation $l): mixed => $this->relatedAttribute($l, 'branch', 'name'),
            'Parent Location' => fn (AssetLocation $l): mixed => $this->relatedAttribute($l, 'parent', 'name'),
            'Created At' => fn (AssetLocation $l): mixed => $this->formatIso8601($l->created_at),
        ];
    }
}
