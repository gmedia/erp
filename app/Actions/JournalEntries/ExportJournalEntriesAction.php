<?php

namespace App\Actions\JournalEntries;

use App\Actions\Concerns\ConfiguredXlsxExportAction;
use App\Exports\JournalEntryExport;

class ExportJournalEntriesAction extends ConfiguredXlsxExportAction
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function buildFilters(array $validated): array
    {
        return $validated;
    }

    protected function filenamePrefix(): string
    {
        return 'journal_entries';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new JournalEntryExport($filters);
    }
}
