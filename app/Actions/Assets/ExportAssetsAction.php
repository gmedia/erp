<?php

namespace App\Actions\Assets;

use App\Exports\AssetExport;
use App\Http\Requests\Assets\ExportAssetRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportAssetsAction
{
    public function execute(ExportAssetRequest $request): JsonResponse
    {
        $filters = array_filter($request->validated());

        $filename = 'assets_export_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new AssetExport($filters), $filePath, 'public');

        $url = Storage::disk('public')->url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
