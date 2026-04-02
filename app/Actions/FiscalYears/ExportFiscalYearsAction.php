<?php

namespace App\Actions\FiscalYears;

use App\Actions\Concerns\SimpleCrudExportAction;
use App\Exports\FiscalYearExport;
use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Maatwebsite\Excel\Concerns\FromQuery;

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

    protected function applyAdditionalFilters(Builder $query, array $validated, FormRequest $request): void
    {
        $this->applyFilledEqualsFilters($query, $validated, $request, ['status']);
    }

    protected function getSortableFields(): array
    {
        return ['id', 'name', 'start_date', 'end_date', 'status', 'created_at', 'updated_at'];
    }
}
