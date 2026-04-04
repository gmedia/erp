<?php

namespace App\Actions\AssetStocktakes;

use App\Exports\AssetStocktakeVarianceExport;
use App\Http\Requests\AssetStocktakes\ExportAssetStocktakeVarianceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportAssetStocktakeVariancesAction
{
    public function execute(ExportAssetStocktakeVarianceRequest $request): JsonResponse
    {
        $fileName = 'asset_stocktake_variances_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = 'exports/' . $fileName;

        Excel::store(new AssetStocktakeVarianceExport($this->buildFilters($request)), $filePath, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($filePath),
            'filename' => $fileName,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFilters(ExportAssetStocktakeVarianceRequest $request): array
    {
        return [
            'asset_stocktake_id' => $request->get('asset_stocktake_id'),
            'branch_id' => $request->get('branch_id'),
            'result' => $request->get('result'),
            'search' => $request->get('search'),
            'sort_by' => $request->get('sort_by', 'checked_at'),
            'sort_direction' => (string) $request->get('sort_direction', 'desc'),
        ];
    }
}
