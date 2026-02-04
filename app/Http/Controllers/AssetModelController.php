<?php

namespace App\Http\Controllers;

use App\Actions\AssetModels\ExportAssetModelsAction;
use App\Actions\AssetModels\IndexAssetModelsAction;
use App\Domain\AssetModels\AssetModelFilterService;
use App\DTOs\AssetModels\UpdateAssetModelData;
use App\Http\Requests\AssetModels\ExportAssetModelRequest;
use App\Http\Requests\AssetModels\IndexAssetModelRequest;
use App\Http\Requests\AssetModels\StoreAssetModelRequest;
use App\Http\Requests\AssetModels\UpdateAssetModelRequest;
use App\Http\Resources\AssetModels\AssetModelCollection;
use App\Http\Resources\AssetModels\AssetModelResource;
use App\Models\AssetModel;
use Illuminate\Http\JsonResponse;

class AssetModelController extends Controller
{
    public function index(IndexAssetModelRequest $request): JsonResponse
    {
        $assetModels = (new IndexAssetModelsAction(app(AssetModelFilterService::class)))->execute($request);

        return (new AssetModelCollection($assetModels))->response();
    }

    public function store(StoreAssetModelRequest $request): JsonResponse
    {
        $assetModel = AssetModel::create($request->validated());

        return (new AssetModelResource($assetModel))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AssetModel $assetModel): JsonResponse
    {
        $assetModel->load(['category']);

        return (new AssetModelResource($assetModel))->response();
    }

    public function update(UpdateAssetModelRequest $request, AssetModel $assetModel): JsonResponse
    {
        $dto = UpdateAssetModelData::fromArray($request->validated());
        $assetModel->update($dto->toArray());

        return (new AssetModelResource($assetModel))->response();
    }

    public function export(ExportAssetModelRequest $request, ExportAssetModelsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    public function destroy(AssetModel $assetModel): JsonResponse
    {
        $assetModel->delete();

        return response()->json(null, 204);
    }
}
