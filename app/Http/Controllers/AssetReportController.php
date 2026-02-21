<?php

namespace App\Http\Controllers;

use App\Actions\Assets\ExportAssetsAction;
use App\Actions\Assets\IndexAssetsAction;
use App\Http\Requests\Assets\ExportAssetRequest;
use App\Http\Requests\Assets\IndexAssetRequest;
use App\Http\Resources\Assets\AssetCollection;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssetReportController extends Controller
{
    /**
     * Display the Asset Register report.
     */
    public function register(IndexAssetRequest $request, IndexAssetsAction $action): Response|AssetCollection
    {
        $assets = $action->execute($request);

        if ($request->wantsJson()) {
            return new AssetCollection($assets);
        }

        return Inertia::render('reports/assets/register/index', [
            'assets' => new AssetCollection($assets),
            'filters' => $request->only([
                'search',
                'asset_category_id',
                'asset_model_id',
                'branch_id',
                'asset_location_id',
                'department_id',
                'employee_id',
                'status',
                'condition',
                'sort_by',
                'sort_direction',
            ]),
        ]);
    }

    /**
     * Export the Asset Register report to Excel.
     */
    public function exportRegister(ExportAssetRequest $request, ExportAssetsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
