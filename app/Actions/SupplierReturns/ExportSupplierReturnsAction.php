<?php

namespace App\Actions\SupplierReturns;

use App\Exports\SupplierReturnExport;
use App\Http\Requests\SupplierReturns\ExportSupplierReturnRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportSupplierReturnsAction
{
    public function execute(ExportSupplierReturnRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'purchase_order' => $validated['purchase_order'] ?? null,
            'goods_receipt' => $validated['goods_receipt'] ?? null,
            'supplier' => $validated['supplier'] ?? null,
            'warehouse' => $validated['warehouse'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'status' => $validated['status'] ?? null,
            'return_date_from' => $validated['return_date_from'] ?? null,
            'return_date_to' => $validated['return_date_to'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $filters = array_filter($filters, static fn ($value) => $value !== null && $value !== '');

        $filename = 'supplier_returns_export_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new SupplierReturnExport($filters), $filePath, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ]);
    }
}
