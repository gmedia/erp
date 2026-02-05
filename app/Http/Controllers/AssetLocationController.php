<?php

namespace App\Http\Controllers;

use App\Actions\AssetLocations\ExportAssetLocationsAction;
use App\Actions\AssetLocations\IndexAssetLocationsAction;
use App\Domain\AssetLocations\AssetLocationFilterService;
use App\DTOs\AssetLocations\UpdateAssetLocationData;
use App\Http\Requests\AssetLocations\ExportAssetLocationRequest;
use App\Http\Requests\AssetLocations\IndexAssetLocationRequest;
use App\Http\Requests\AssetLocations\StoreAssetLocationRequest;
use App\Http\Requests\AssetLocations\UpdateAssetLocationRequest;
use App\Http\Resources\AssetLocations\AssetLocationCollection;
use App\Http\Resources\AssetLocations\AssetLocationResource;
use App\Models\AssetLocation;
use Illuminate\Http\JsonResponse;

class AssetLocationController extends Controller
{
    public function index(IndexAssetLocationRequest $request): JsonResponse
    {
        $assetLocations = (new IndexAssetLocationsAction(app(AssetLocationFilterService::class)))->execute($request);

        return (new AssetLocationCollection($assetLocations))->response();
    }

    public function store(StoreAssetLocationRequest $request): JsonResponse
    {
        $assetLocation = AssetLocation::create($request->validated());

        return (new AssetLocationResource($assetLocation))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AssetLocation $assetLocation): JsonResponse
    {
        $assetLocation->load(['branch', 'parent']);

        return (new AssetLocationResource($assetLocation))->response();
    }

    public function update(UpdateAssetLocationRequest $request, AssetLocation $assetLocation): JsonResponse
    {
        $dto = UpdateAssetLocationData::fromArray($request->validated());
        $assetLocation->update($dto->toArray());

        return (new AssetLocationResource($assetLocation))->response();
    }

    public function export(ExportAssetLocationRequest $request, ExportAssetLocationsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    public function destroy(AssetLocation $assetLocation): JsonResponse
    {
        $assetLocation->delete();

        return response()->json(null, 204);
    }
}
