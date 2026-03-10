<?php

namespace App\Actions\PurchaseOrders;

use App\Exports\PurchaseOrderExport;
use App\Http\Requests\PurchaseOrders\ExportPurchaseOrderRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportPurchaseOrdersAction
{
    public function execute(ExportPurchaseOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'supplier' => $validated['supplier'] ?? null,
            'warehouse' => $validated['warehouse'] ?? null,
            'status' => $validated['status'] ?? null,
            'currency' => $validated['currency'] ?? null,
            'order_date_from' => $validated['order_date_from'] ?? null,
            'order_date_to' => $validated['order_date_to'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $filters = array_filter($filters, static fn ($value) => $value !== null && $value !== '');

        $filename = 'purchase_orders_export_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new PurchaseOrderExport($filters), $filePath, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ]);
    }
}
