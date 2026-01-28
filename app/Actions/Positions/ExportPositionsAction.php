<?php

namespace App\Actions\Positions;

use App\Actions\Concerns\SimpleCrudExportAction;
use App\Exports\PositionExport;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;

/**
 * Action to export positions to Excel based on filters.
 */
class ExportPositionsAction extends SimpleCrudExportAction
{
    protected function getModelClass(): string
    {
        return Position::class;
    }

    protected function getExportInstance(array $filters, ?Builder $query): FromQuery
    {
        return new PositionExport($filters, $query);
    }

    protected function getFilenamePrefix(): string
    {
        return 'positions';
    }
}
