<?php

namespace App\Actions\AssetMaintenances;

use App\Exports\AssetMaintenanceExport;
use App\Http\Requests\AssetMaintenances\ExportAssetMaintenanceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportAssetMaintenancesAction
{
    public function execute(ExportAssetMaintenanceRequest $request): JsonResponse
    {
        $filters = array_filter($request->validated(), fn ($value) => $value !== null && $value !== '');

        $filename = 'asset-maintenances-export-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new AssetMaintenanceExport($filters), $filePath, 'public');

        return response()->json([
            'url' => Storage::url($filePath),
            'filename' => $filename,
        ]);
    }
}
