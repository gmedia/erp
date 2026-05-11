<?php

namespace App\Actions\PeriodClosings;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\PeriodClosingExport;

class ExportPeriodClosingsAction extends ConfiguredTransactionExportAction
{
    protected function filenamePrefix(): string
    {
        return 'period_closings';
    }

    protected function makeExport(array $filters): object
    {
        return new PeriodClosingExport($filters);
    }
}
