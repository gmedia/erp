<?php

namespace App\Actions\Reports;

use App\Actions\Concerns\ExportsReportToExcel;
use App\Exports\TrialBalanceReportExport;

class ExportTrialBalanceReportAction
{
    use ExportsReportToExcel;

    protected function filenamePrefix(): string
    {
        return 'trial_balance_report';
    }

    protected function makeExport(array $filters): object
    {
        return new TrialBalanceReportExport($filters);
    }
}
