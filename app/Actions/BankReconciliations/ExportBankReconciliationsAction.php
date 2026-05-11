<?php

namespace App\Actions\BankReconciliations;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\BankReconciliationExport;

class ExportBankReconciliationsAction extends ConfiguredTransactionExportAction
{
    protected function filenamePrefix(): string
    {
        return 'bank_reconciliations';
    }

    protected function makeExport(array $filters): object
    {
        return new BankReconciliationExport($filters);
    }
}
