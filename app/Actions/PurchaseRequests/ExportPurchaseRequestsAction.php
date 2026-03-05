<?php

namespace App\Actions\PurchaseRequests;

use App\Exports\PurchaseRequestExport;
use App\Http\Requests\PurchaseRequests\ExportPurchaseRequestRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportPurchaseRequestsAction
{
    public function execute(ExportPurchaseRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'branch' => $validated['branch'] ?? null,
            'department' => $validated['department'] ?? null,
            'requested_by' => $validated['requested_by'] ?? null,
            'priority' => $validated['priority'] ?? null,
            'status' => $validated['status'] ?? null,
            'request_date_from' => $validated['request_date_from'] ?? null,
            'request_date_to' => $validated['request_date_to'] ?? null,
            'required_date_from' => $validated['required_date_from'] ?? null,
            'required_date_to' => $validated['required_date_to'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $filters = array_filter($filters, static fn ($value) => $value !== null && $value !== '');

        $filename = 'purchase_requests_export_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new PurchaseRequestExport($filters), $filePath, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ]);
    }
}
