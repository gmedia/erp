<?php

namespace App\Actions\Concerns;

abstract class ConfiguredPartyExportAction extends ConfiguredTimestampExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'branch_id' => null,
            'category_id' => null,
            'status' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }
}
