<?php

namespace App\Actions\GoodsReceipts;

use App\Exports\GoodsReceiptExport;
use App\Http\Requests\GoodsReceipts\ExportGoodsReceiptRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportGoodsReceiptsAction
{
    public function execute(ExportGoodsReceiptRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'purchase_order' => $validated['purchase_order'] ?? null,
            'warehouse' => $validated['warehouse'] ?? null,
            'status' => $validated['status'] ?? null,
            'received_by' => $validated['received_by'] ?? null,
            'receipt_date_from' => $validated['receipt_date_from'] ?? null,
            'receipt_date_to' => $validated['receipt_date_to'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $filters = array_filter($filters, static fn ($value) => $value !== null && $value !== '');

        $filename = 'goods_receipts_export_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new GoodsReceiptExport($filters), $filePath, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ]);
    }
}
