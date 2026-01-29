<?php

namespace App\Actions\Units;

use App\Actions\Concerns\SimpleCrudExportAction;
use App\Exports\UnitExport;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;

class ExportUnitsAction extends SimpleCrudExportAction
{
    protected function getModelClass(): string
    {
        return Unit::class;
    }

    protected function getExportInstance(array $filters, ?Builder $query): FromQuery
    {
        return new UnitExport($filters, $query);
    }

    protected function getFilenamePrefix(): string
    {
        return 'units';
    }
}
