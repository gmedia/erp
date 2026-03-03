<?php

namespace App\Actions\StockMovements;

use App\Exports\StockMovementsExport;
use App\Http\Requests\StockMovements\ExportStockMovementRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportStockMovementsAction
{
    public function execute(ExportStockMovementRequest $request): JsonResponse
    {
        $filters = array_filter($request->validated(), static fn ($v) => $v !== null && $v !== '');

        $format = $request->get('format', 'xlsx');
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        $filename = 'stock_movements_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.' . $extension;
        $filePath = 'exports/' . $filename;

        Excel::store(new StockMovementsExport($filters), $filePath, 'public', $writerType);

        $url = Storage::disk('public')->url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}

