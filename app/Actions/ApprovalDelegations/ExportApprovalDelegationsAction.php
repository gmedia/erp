<?php

namespace App\Actions\ApprovalDelegations;

use App\Actions\ApprovalDelegations\Concerns\InteractsWithApprovalDelegationQuery;
use App\Actions\Concerns\ConfiguredTimestampExportAction;
use App\Domain\ApprovalDelegations\ApprovalDelegationFilterService;
use App\Exports\ApprovalDelegations\ApprovalDelegationExport;

class ExportApprovalDelegationsAction extends ConfiguredTimestampExportAction
{
    use InteractsWithApprovalDelegationQuery;

    public function __construct(
        private readonly ApprovalDelegationFilterService $filterService
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'id' => null,
            'delegator_user_id' => null,
            'delegate_user_id' => null,
            'approvable_type' => null,
            'start_date' => null,
            'end_date' => null,
            'is_active' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'approval_delegations';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        $query = $this->buildFilteredQuery(
            $this->filterService,
            $filters,
            [
                'id',
                'delegator_user_id',
                'delegate_user_id',
                'approvable_type',
                'start_date',
                'end_date',
                'is_active',
                'created_at',
            ],
        );

        return new ApprovalDelegationExport($query);
    }
}
