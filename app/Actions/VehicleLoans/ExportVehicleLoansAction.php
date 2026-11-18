<?php

namespace App\Actions\VehicleLoans;

use App\Actions\Concerns\ConfiguredWriterExportAction;
use App\Exports\VehicleLoanExport;

class ExportVehicleLoansAction extends ConfiguredWriterExportAction
{
    protected function filenamePrefix(): string
    {
        return 'vehicle_loan_report';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new VehicleLoanExport($filters);
    }
}
