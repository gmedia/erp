<?php

namespace App\Actions\ApprovalFlows;

use App\Exports\ApprovalFlowExport;
use App\Http\Requests\ApprovalFlows\ExportApprovalFlowRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportApprovalFlowsAction
{
    public function execute(ExportApprovalFlowRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'approvable_type' => $validated['approvable_type'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $filters = array_filter($filters, fn($val) => $val !== null && $val !== '');

        $filename = 'approval_flows_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new ApprovalFlowExport($filters), $filePath, 'public');

        // Match implementation in other export actions to ensure export URLs are generated relative to the public disk
        $url = Storage::disk('public')->url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
