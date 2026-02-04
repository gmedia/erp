<?php

namespace App\Http\Controllers;

use App\Actions\AssetCategories\ExportAssetCategoriesAction;
use App\Actions\AssetCategories\IndexAssetCategoriesAction;
use App\Http\Requests\AssetCategories\ExportAssetCategoryRequest;
use App\Http\Requests\AssetCategories\IndexAssetCategoryRequest;
use App\Http\Requests\AssetCategories\StoreAssetCategoryRequest;
use App\Http\Requests\AssetCategories\UpdateAssetCategoryRequest;
use App\Http\Resources\AssetCategories\AssetCategoryCollection;
use App\Http\Resources\AssetCategories\AssetCategoryResource;
use App\Models\AssetCategory;
use Illuminate\Http\JsonResponse;

class AssetCategoryController extends Controller
{
    public function index(IndexAssetCategoryRequest $request): JsonResponse
    {
        $categories = (new IndexAssetCategoriesAction())->execute($request);

        return (new AssetCategoryCollection($categories))->response();
    }

    public function store(StoreAssetCategoryRequest $request): JsonResponse
    {
        $category = AssetCategory::create($request->validated());

        return (new AssetCategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function show(AssetCategory $assetCategory): JsonResponse
    {
        return (new AssetCategoryResource($assetCategory))->response();
    }

    public function update(UpdateAssetCategoryRequest $request, AssetCategory $assetCategory): JsonResponse
    {
        $assetCategory->update($request->validated());

        return (new AssetCategoryResource($assetCategory))->response();
    }

    public function destroy(AssetCategory $assetCategory): JsonResponse
    {
        $assetCategory->delete();

        return response()->json(null, 204);
    }

    public function export(ExportAssetCategoryRequest $request): JsonResponse
    {
        return (new ExportAssetCategoriesAction())->execute($request);
    }
}
