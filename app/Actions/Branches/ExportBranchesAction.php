<?php

namespace App\Actions\Branches;

use App\Actions\Concerns\SimpleCrudExportAction;
use App\Exports\BranchExport;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;

/**
 * Action to export branches to Excel based on filters.
 */
class ExportBranchesAction extends SimpleCrudExportAction
{
    protected function getModelClass(): string
    {
        return Branch::class;
    }

    protected function getExportInstance(array $filters, ?Builder $query): FromQuery
    {
        return new BranchExport($filters, $query);
    }

    protected function getFilenamePrefix(): string
    {
        return 'branches';
    }
}
