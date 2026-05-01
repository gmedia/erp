<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\AssetModel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class AssetModelExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = AssetModel::query()->with(['category']);

        $this->applySearchFilter($query, $this->filters, ['model_name', 'manufacturer']);
        $this->applyExactFilters($query, $this->filters, ['asset_category_id' => 'asset_category_id']);

        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->normalizeSortDirection($this->filters);
        $allowedSortColumns = [
            'id',
            'model_name',
            'manufacturer',
            'category',
            'asset_category_id',
            'created_at',
            'updated_at',
        ];

        if ($sortBy === 'category') {
            $query
                ->leftJoin('asset_categories', 'asset_models.asset_category_id', '=', 'asset_categories.id')
                ->select('asset_models.*')
                ->orderBy('asset_categories.name', $sortDirection);
        } elseif (in_array($sortBy, $allowedSortColumns, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($assetModel): array
    {
        return $this->mapExportRow($assetModel, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (AssetModel $m): mixed => $m->id,
            'Model Name' => fn (AssetModel $m): mixed => $m->model_name,
            'Manufacturer' => fn (AssetModel $m): mixed => $m->manufacturer,
            'Category' => fn (AssetModel $m): mixed => $this->relatedAttribute($m, 'category', 'name'),
            'Specs' => fn (AssetModel $m): mixed => $m->specs ? json_encode($m->specs) : '',
            'Created At' => fn (AssetModel $m): mixed => $this->formatIso8601($m->created_at),
        ];
    }
}
