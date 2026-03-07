<?php

namespace App\Actions\StockAdjustments;

use App\Exports\StockAdjustmentExport;
use App\Http\Requests\StockAdjustments\ExportStockAdjustmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportStockAdjustmentsAction
{
    public function execute(ExportStockAdjustmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'status' => $validated['status'] ?? null,
            'adjustment_type' => $validated['adjustment_type'] ?? null,
            'inventory_stocktake_id' => $validated['inventory_stocktake_id'] ?? null,
            'adjustment_date_from' => $validated['adjustment_date_from'] ?? null,
            'adjustment_date_to' => $validated['adjustment_date_to'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $filters = array_filter($filters);

        $filename = 'stock_adjustments_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new StockAdjustmentExport($filters), $filePath, 'public');

        $url = Storage::disk('public')->url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
