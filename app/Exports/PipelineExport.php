<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\Pipeline;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class PipelineExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = Pipeline::query()->with(['creator']);

        $this->applySearchFilter($query, $this->filters, ['name', 'code', 'description']);

        if (isset($this->filters['entity_type']) && $this->filters['entity_type'] !== '') {
            $query->where('entity_type', $this->filters['entity_type']);
        }
        if (isset($this->filters['is_active']) && $this->filters['is_active'] !== '') {
            $query->where('is_active', filter_var($this->filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->normalizeSortDirection($this->filters);
        $allowedSortColumns = ['name', 'code', 'entity_type', 'version', 'is_active', 'created_at'];

        if ($sortBy === 'created_by') {
            $query->leftJoin('users as creator', 'pipelines.created_by', '=', 'creator.id')
                ->orderBy('creator.name', $sortDirection)
                ->select('pipelines.*');
        } elseif (in_array($sortBy, $allowedSortColumns, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Code', 'Entity Type', 'Version', 'Active', 'Created By', 'Created At'];
    }

    public function map($pipeline): array
    {
        return [
            $pipeline->id,
            $pipeline->name,
            $pipeline->code,
            $pipeline->entity_type,
            $pipeline->version,
            $pipeline->is_active ? 'Yes' : 'No',
            $pipeline->creator?->name,
            $pipeline->created_at?->toIso8601String(),
        ];
    }
}
