<?php

namespace App\Actions\CoaVersions;

use App\Actions\Concerns\SimpleCrudExportAction;
use App\Exports\CoaVersionExport;
use App\Models\CoaVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Maatwebsite\Excel\Concerns\FromQuery;

/**
 * Action to export COA versions to Excel based on filters.
 */
class ExportCoaVersionsAction extends SimpleCrudExportAction
{
    protected function createQuery(): Builder
    {
        return parent::createQuery()->with('fiscalYear');
    }

    protected function getModelClass(): string
    {
        return CoaVersion::class;
    }

    protected function getExportInstance(array $filters, ?Builder $query): FromQuery
    {
        return new CoaVersionExport($filters, $query);
    }

    protected function getFilenamePrefix(): string
    {
        return 'coa_versions';
    }

    protected function applyAdditionalFilters(Builder $query, array $validated, FormRequest $request): void
    {
        $this->applyFilledEqualsFilters($query, $validated, $request, [
            'status',
            'fiscal_year_id',
        ]);
    }

    protected function getSortableFields(): array
    {
        return ['id', 'name', 'fiscal_year_id', 'status', 'created_at', 'updated_at'];
    }
}
