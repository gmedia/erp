<?php

namespace App\Actions\AccountMappings;

use App\Actions\AccountMappings\Concerns\BuildsAccountMappingQuery;
use App\Actions\Concerns\ConfiguredTimestampExportAction;
use App\Domain\AccountMappings\AccountMappingFilterService;
use App\Exports\AccountMappingExport;

class ExportAccountMappingsAction extends ConfiguredTimestampExportAction
{
    use BuildsAccountMappingQuery;

    public function __construct(
        private AccountMappingFilterService $filterService
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'type' => null,
            'source_coa_version_id' => null,
            'target_coa_version_id' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'account_mappings';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        $query = $this->buildAccountMappingQuery(
            $this->filterService,
            [
                'type' => $filters['type'] ?? null,
                'source_coa_version_id' => $filters['source_coa_version_id'] ?? null,
                'target_coa_version_id' => $filters['target_coa_version_id'] ?? null,
            ],
            isset($filters['search']) ? (string) $filters['search'] : null,
            (string) ($filters['sort_by'] ?? 'created_at'),
            (string) ($filters['sort_direction'] ?? 'desc'),
        );

        return new AccountMappingExport($filters, $query);
    }
}
