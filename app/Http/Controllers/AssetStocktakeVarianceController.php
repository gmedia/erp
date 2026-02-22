<?php

namespace App\Http\Controllers;

use App\Actions\AssetStocktakes\ExportAssetStocktakeVariancesAction;
use App\Actions\AssetStocktakes\IndexAssetStocktakeVarianceAction;
use App\Http\Requests\AssetStocktakes\ExportAssetStocktakeVarianceRequest;
use App\Http\Requests\AssetStocktakes\IndexAssetStocktakeVarianceRequest;
use App\Http\Resources\AssetStocktakes\AssetStocktakeVarianceCollection;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssetStocktakeVarianceController extends Controller
{
    public function page(): Response
    {
        return Inertia::render('reports/asset-stocktake-variances/index');
    }

    public function index(IndexAssetStocktakeVarianceRequest $request, IndexAssetStocktakeVarianceAction $action): JsonResponse
    {
        $variances = $action->execute($request);
        return response()->json(new AssetStocktakeVarianceCollection($variances));
    }

    public function export(ExportAssetStocktakeVarianceRequest $request, ExportAssetStocktakeVariancesAction $action)
    {
        return $action->execute($request);
    }
}
