<?php

namespace App\Actions\Accounts;

use App\Actions\Concerns\ConfiguredTimestampExportAction;
use App\Exports\AccountExport;

class ExportAccountsAction extends ConfiguredTimestampExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'coa_version_id' => null,
            'search' => null,
            'type' => null,
            'is_active' => null,
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'accounts';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new AccountExport($filters);
    }
}
