<?php

namespace App\Actions\Products;

use App\Exports\ProductExport;
use App\Http\Requests\Products\ExportProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportProductsAction
{
    public function execute(ExportProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Map keys (request uses 'category', not 'category_id' for simplicity in filters)
        $filters = [
            'search' => $validated['search'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'unit_id' => $validated['unit_id'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'type' => $validated['type'] ?? null,
            'status' => $validated['status'] ?? null,
            'billing_model' => $validated['billing_model'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $filters = array_filter($filters, fn($value) => $value !== null);

        $filename = 'products_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new ProductExport($filters), $filePath, 'public');

        $url = Storage::disk('public')->url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
