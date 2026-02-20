<?php

namespace App\Actions\AssetStocktakes;

use App\Exports\AssetStocktakeExport;
use App\Http\Requests\AssetStocktakes\ExportAssetStocktakeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportAssetStocktakesAction
{
    public function execute(ExportAssetStocktakeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'branch' => $validated['branch'] ?? null,
            'status' => $validated['status'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $filters = array_filter($filters, fn($value) => !is_null($value));

        $filename = 'asset_stocktakes_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new AssetStocktakeExport($filters), $filePath, 'public');

        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
