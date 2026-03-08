<?php

namespace App\Http\Controllers;

use App\Actions\Assets\ExportAssetsAction;
use App\Actions\Assets\IndexAssetsAction;
use App\Http\Requests\Assets\ExportAssetRequest;
use App\Http\Requests\Assets\IndexAssetRequest;
use App\Http\Resources\Assets\AssetCollection;
use Illuminate\Http\JsonResponse;

class AssetReportController extends Controller
{
    /**
     * Display the Asset Register report.
     */
    public function register(IndexAssetRequest $request, IndexAssetsAction $action): AssetCollection
    {
        $assets = $action->execute($request);

        return new AssetCollection($assets);
    }

    /**
     * Export the Asset Register report to Excel.
     */
    public function exportRegister(ExportAssetRequest $request, ExportAssetsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
