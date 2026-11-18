<?php

namespace App\Actions\VehicleLoans;

use App\Actions\Concerns\ConfiguredWriterExportAction;
use App\Exports\VehicleLoanExport;
use Illuminate\Support\Facades\Auth;

class ExportVehicleLoansAction extends ConfiguredWriterExportAction
{
    protected function filenamePrefix(): string
    {
        return 'vehicle_loan_report';
    }

    /**
     * Force the caller's branch unless they are allowed to view all branches.
     * Prevents exporting other branches' loan data via a crafted filter.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function buildFilters(array $validated): array
    {
        $filters = parent::buildFilters($validated);
        $employee = Auth::user()?->employee;

        if ($employee && ! $employee->hasPermission('view_all_branches')) {
            $branchId = $employee->currentEmployment?->branch_id;

            if ($branchId !== null) {
                $filters['branch_id'] = $branchId;
            }
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new VehicleLoanExport($filters);
    }
}
