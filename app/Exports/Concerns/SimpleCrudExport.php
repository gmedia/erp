<?php

namespace App\Exports\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Base export class for simple CRUD entities.
 *
 * Provides common functionality for exporting entities with:
 * - id, name, created_at, updated_at columns
 * - Search and sorting filters
 * - Auto-sized columns
 * - Bold header row
 */
abstract class SimpleCrudExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * @var array<string, mixed>
     */
    protected $filters;

    /**
     * @var Builder|null
     */
    protected $query;

    /**
     * Create a new export instance.
     *
     * @param  array<string, mixed>  $filters
     * @param  Builder|null  $query
     */
    public function __construct(array $filters = [], ?Builder $query = null)
    {
        $this->filters = $filters;
        $this->query = $query;
    }

    /**
     * Get the model class for the entity.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    abstract protected function getModelClass(): string;

    /**
     * Get the sortable fields for this entity.
     *
     * @return array<int, string>
     */
    protected function getSortableFields(): array
    {
        return ['id', 'name', 'created_at', 'updated_at'];
    }

    /**
     * Build the query for the export.
     */
    public function query(): Builder
    {
        if ($this->query) {
            return $this->query;
        }

        $modelClass = $this->getModelClass();
        $query = $modelClass::query();

        // Apply search filter if provided (used by frontend)
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        // Apply name filter if provided (fallback for direct API calls)
        if (!empty($this->filters['name'])) {
            $name = $this->filters['name'];
            $query->where('name', 'like', "%{$name}%");
        }

        // Apply sorting
        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';

        // Whitelist sortable columns to prevent injection
        $allowedSorts = $this->getSortableFields();
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    /**
     * Define the headings for the Excel sheet.
     *
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * Map each model to a row.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array<int, mixed>
     */
    public function map($model): array
    {
        return [
            $model->id,
            $model->name,
            $model->created_at ? $model->created_at->toIso8601String() : null,
            $model->updated_at ? $model->updated_at->toIso8601String() : null,
        ];
    }

    /**
     * Apply styles to the worksheet.
     *
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Bold the header row
            1 => ['font' => ['bold' => true]],
        ];
    }
}
