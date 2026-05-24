<?php

namespace App\Actions\Reports;

use App\Actions\Concerns\ExportsReportToExcel;
use App\Exports\TrialBalanceFinancialReportExport;

class ExportTrialBalanceFinancialReportAction
{
    use ExportsReportToExcel;

    protected function filenamePrefix(): string
    {
        return 'trial_balance_financial_report';
    }

    protected function makeExport(array $filters): object
    {
        return new TrialBalanceFinancialReportExport($filters);
    }
}
