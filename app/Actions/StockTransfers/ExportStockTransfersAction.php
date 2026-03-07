<?php

namespace App\Actions\StockTransfers;

use App\Exports\StockTransferExport;
use App\Http\Requests\StockTransfers\ExportStockTransferRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportStockTransfersAction
{
    public function execute(ExportStockTransferRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'from_warehouse_id' => $validated['from_warehouse_id'] ?? null,
            'to_warehouse_id' => $validated['to_warehouse_id'] ?? null,
            'status' => $validated['status'] ?? null,
            'transfer_date_from' => $validated['transfer_date_from'] ?? null,
            'transfer_date_to' => $validated['transfer_date_to'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $filters = array_filter($filters);

        $filename = 'stock_transfers_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new StockTransferExport($filters), $filePath, 'public');

        $url = Storage::disk('public')->url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
