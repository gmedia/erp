<?php

namespace App\Actions\Assets;

use App\Exports\AssetExport;
use App\Http\Requests\Assets\ExportAssetRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportAssetsAction
{
    public function execute(ExportAssetRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'branch' => $validated['branch'] ?? null,
            'category' => $validated['category'] ?? null,
            'status' => $validated['status'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $filters = array_filter($filters);

        $filename = 'assets_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new AssetExport($filters), $filePath, 'public');

        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
