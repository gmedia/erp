<?php

namespace App\Actions\AssetMovements;

use App\Exports\AssetMovementExport;
use App\Http\Requests\Assets\ExportAssetMovementRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportAssetMovementsAction
{
    public function execute(ExportAssetMovementRequest $request): JsonResponse
    {
        $filters = array_filter($request->validated());

        $filename = 'asset_movements_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new AssetMovementExport($filters), $filePath, 'public');

        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
