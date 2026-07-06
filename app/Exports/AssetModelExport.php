<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\AssetModel;
use Illuminate\Database\Eloquent\Builder;

class AssetModelExport extends BaseExport
{
    public function __construct(protected readonly array $filters = []) {}

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
