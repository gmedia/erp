<?php

namespace App\Actions\ApprovalDelegations;

use App\Domain\ApprovalDelegations\ApprovalDelegationFilterService;
use App\Exports\ApprovalDelegations\ApprovalDelegationExport;
use App\Models\ApprovalDelegation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportApprovalDelegationsAction
{
    public function __construct(
        private readonly ApprovalDelegationFilterService $filterService
    ) {}

    public function execute(array $filters): JsonResponse
    {
        $query = ApprovalDelegation::query()
            ->with(['delegator:id,name', 'delegate:id,name']);

        // Same filtering logic as index
        if (! empty($filters['search'])) {
            $this->filterService->applySearch(
                $query,
                $filters['search'],
                ['reason'],
                [
                    'delegator' => ['name'],
                    'delegate' => ['name'],
                ]
            );
        }

        $this->filterService->applyAdvancedFilters($query, $filters);

        $this->filterService->applySorting(
            $query,
            $filters['sort_by'] ?? 'created_at',
            $filters['sort_direction'] ?? 'desc',
            ['id', 'delegator_user_id', 'delegate_user_id', 'approvable_type', 'start_date', 'end_date', 'is_active', 'created_at']
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
