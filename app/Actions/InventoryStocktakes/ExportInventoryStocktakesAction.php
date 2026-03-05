<?php

namespace App\Actions\InventoryStocktakes;

use App\Exports\InventoryStocktakeExport;
use App\Http\Requests\InventoryStocktakes\ExportInventoryStocktakeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportInventoryStocktakesAction
{
    public function execute(ExportInventoryStocktakeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'product_category_id' => $validated['product_category_id'] ?? null,
            'status' => $validated['status'] ?? null,
            'stocktake_date_from' => $validated['stocktake_date_from'] ?? null,
            'stocktake_date_to' => $validated['stocktake_date_to'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $filters = array_filter($filters);

        $filename = 'inventory_stocktakes_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new InventoryStocktakeExport($filters), $filePath, 'public');

        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}

