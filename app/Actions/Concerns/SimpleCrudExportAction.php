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
     * Get the model class for the entity.
     *
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    abstract protected function getModelClass(): string;

    /**
     * Get the export class for this entity.
     *
     * @param  array<string, mixed>  $filters
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return \Maatwebsite\Excel\Concerns\FromQuery
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
     * Get the sortable fields for this entity.
     *
     * @return array<int, string>
     */
    protected function getSortableFields(): array
    {
        return ['id', 'name', 'created_at', 'updated_at'];
    }

    /**
     * Execute the export action.
     *
     * @param  \Illuminate\Foundation\Http\FormRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function execute(FormRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $modelClass = $this->getModelClass();
        $query = $modelClass::query();

        if ($request->filled('search')) {
            $this->applySearch($query, $validated['search'], $this->getSearchFields());
        }

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
}
