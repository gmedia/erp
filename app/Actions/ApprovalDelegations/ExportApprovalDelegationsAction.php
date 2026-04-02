<?php

namespace App\Actions\ApprovalDelegations;

use App\Actions\ApprovalDelegations\Concerns\InteractsWithApprovalDelegationQuery;
use App\Domain\ApprovalDelegations\ApprovalDelegationFilterService;
use App\Exports\ApprovalDelegations\ApprovalDelegationExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportApprovalDelegationsAction
{
    use InteractsWithApprovalDelegationQuery;

    public function __construct(
        private readonly ApprovalDelegationFilterService $filterService
    ) {}

    public function execute(array $filters): JsonResponse
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

        // Generate filename with timestamp
        $filename = 'approval_delegations_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Store the file in storage/app/public/exports/
        $filePath = 'exports/' . $filename;

        // Generate the Excel file using public disk
        $export = new ApprovalDelegationExport($query);
        Excel::store($export, $filePath, 'public');

        // Generate the public URL for download
        $url = Storage::disk('public')->url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
