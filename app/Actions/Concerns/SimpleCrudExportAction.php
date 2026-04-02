<?php

namespace App\Actions\Concerns;

use App\Domain\Concerns\BaseFilterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Base action for exporting simple CRUD entities to Excel.
 *
 * Extend this class for simple entities that only need name-based search
 * and standard sorting on id, name, created_at, updated_at fields.
 */
abstract class SimpleCrudExportAction
{
    use BaseFilterService;

    /**
     * Execute the export action.
     */
    public function execute(FormRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = $this->createQuery();

        if ($request->filled('search')) {
            $this->applySearch($query, $validated['search'], $this->getSearchFields());
        }

        $this->applyAdditionalFilters($query, $validated, $request);

        $this->applySorting(
            $query,
            $validated['sort_by'] ?? 'created_at',
            $validated['sort_direction'] ?? 'desc',
            $this->getSortableFields()
        );

        // Generate filename with timestamp
        $filename = $this->getFilenamePrefix() . '_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Store the file in storage/app/public/exports/
        $filePath = 'exports/' . $filename;

        // Generate the Excel file using public disk
        $export = $this->getExportInstance([], $query);
        Excel::store($export, $filePath, 'public');

        // Generate the public URL for download
        $url = Storage::disk('public')->url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }

    protected function createQuery(): Builder
    {
        $modelClass = $this->getModelClass();

        return $modelClass::query();
    }

    /**
     * Get the model class for the entity.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    abstract protected function getModelClass(): string;

    /**
     * Get the export class for this entity.
     *
     * @param  array<string, mixed>  $filters
     */
    abstract protected function getExportInstance(array $filters, ?Builder $query): FromQuery;

    /**
     * Get the filename prefix for exports (e.g., 'departments', 'positions').
     */
    abstract protected function getFilenamePrefix(): string;

    /**
     * Get the searchable fields for this entity.
     *
     * @return array<int, string>
     */
    protected function getSearchFields(): array
    {
        return ['name'];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function applyAdditionalFilters(Builder $query, array $validated, FormRequest $request): void
    {
        $this->applyFilledEqualsFilters($query, $validated, $request, []);
    }

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
     * @param  array<string, mixed>  $validated
     * @param  array<int|string, string>  $filters
     */
    protected function applyFilledEqualsFilters(Builder $query, array $validated, FormRequest $request, array $filters): void
    {
        foreach ($filters as $field => $column) {
            $requestField = is_int($field) ? $column : $field;

            if ($request->filled($requestField)) {
                $query->where($column, $validated[$requestField]);
            }
        }
    }
}
