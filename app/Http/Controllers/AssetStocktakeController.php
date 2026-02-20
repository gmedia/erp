<?php

namespace App\Http\Controllers;

use App\Actions\AssetStocktakes\ExportAssetStocktakesAction;
use App\Actions\AssetStocktakes\IndexAssetStocktakesAction;
use App\DTOs\AssetStocktakes\UpdateAssetStocktakeData;
use App\Http\Requests\AssetStocktakes\ExportAssetStocktakeRequest;
use App\Http\Requests\AssetStocktakes\IndexAssetStocktakeRequest;
use App\Http\Requests\AssetStocktakes\StoreAssetStocktakeRequest;
use App\Http\Requests\AssetStocktakes\UpdateAssetStocktakeRequest;
use App\Http\Resources\AssetStocktakes\AssetStocktakeCollection;
use App\Http\Resources\AssetStocktakes\AssetStocktakeResource;
use App\Models\AssetStocktake;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AssetStocktakeController extends Controller
{
    public function index(IndexAssetStocktakeRequest $request, IndexAssetStocktakesAction $action): JsonResponse
    {
        $stocktakes = $action->execute($request);
        return (new AssetStocktakeCollection($stocktakes))->response();
    }

    public function store(StoreAssetStocktakeRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();
        
        $stocktake = AssetStocktake::create($data);

        return (new AssetStocktakeResource($stocktake))->response()->setStatusCode(201);
    }

    public function show(AssetStocktake $assetStocktake): JsonResponse
    {
        $assetStocktake->load(['branch', 'createdBy']);
        return (new AssetStocktakeResource($assetStocktake))->response();
    }

    public function update(UpdateAssetStocktakeRequest $request, AssetStocktake $assetStocktake): JsonResponse
    {
        $dto = UpdateAssetStocktakeData::fromArray($request->validated());
        $assetStocktake->update($dto->toArray());

        return (new AssetStocktakeResource($assetStocktake))->response();
    }

    public function destroy(AssetStocktake $assetStocktake): JsonResponse
    {
        $assetStocktake->delete();
        return response()->json(null, 204);
    }

    public function export(ExportAssetStocktakeRequest $request, ExportAssetStocktakesAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
