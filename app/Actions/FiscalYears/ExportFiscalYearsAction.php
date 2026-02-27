<?php

namespace App\Actions\FiscalYears;

use App\Actions\Concerns\SimpleCrudExportAction;
use App\Exports\FiscalYearExport;
use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Action to export fiscal years to Excel based on filters.
 */
class ExportFiscalYearsAction extends SimpleCrudExportAction
{
    protected function getModelClass(): string
    {
        return FiscalYear::class;
    }

    protected function getExportInstance(array $filters, ?Builder $query): FromQuery
    {
        return new FiscalYearExport($filters, $query);
    }

    protected function getFilenamePrefix(): string
    {
        return 'fiscal_years';
    }

    protected function getSortableFields(): array
    {
        return ['id', 'name', 'start_date', 'end_date', 'status', 'created_at', 'updated_at'];
    }

    public function execute(FormRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $modelClass = $this->getModelClass();
        $query = $modelClass::query();

        if ($request->filled('search')) {
            $this->applySearch($query, $validated['search'], $this->getSearchFields());
        }

        if ($request->filled('status')) {
            $query->where('status', $validated['status']);
        }

        $this->applySorting(
            $query,
            $validated['sort_by'] ?? 'created_at',
            $validated['sort_direction'] ?? 'desc',
            $this->getSortableFields()
        );

        // Generate filename with timestamp
        $filename = $this->getFilenamePrefix() . '_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        // Generate the Excel file
        $export = $this->getExportInstance([], $query);
        Excel::store($export, $filePath, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ]);
    }
}
