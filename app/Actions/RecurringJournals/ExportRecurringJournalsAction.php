<?php

namespace App\Actions\RecurringJournals;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\RecurringJournalExport;

class ExportRecurringJournalsAction extends ConfiguredTransactionExportAction
{
    protected function filenamePrefix(): string
    {
        return 'recurring_journals';
    }

    protected function makeExport(array $filters): object
    {
        return new RecurringJournalExport($filters);
    }
}
