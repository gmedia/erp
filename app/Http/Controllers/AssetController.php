<?php

namespace App\Http\Controllers;

use App\Actions\Assets\{IndexAssetsAction, ExportAssetsAction};
use App\Http\Requests\Assets\{IndexAssetRequest, StoreAssetRequest, UpdateAssetRequest, ExportAssetRequest};
use App\Http\Resources\Assets\{AssetResource, AssetCollection};
use App\DTOs\Assets\UpdateAssetData;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function index(IndexAssetRequest $request, IndexAssetsAction $action): Response|AssetCollection
    {
        $assets = $action->execute($request);

        if ($request->wantsJson()) {
            return new AssetCollection($assets);
        }

        return Inertia::render('assets/index', [
            'assets' => new AssetCollection($assets),
            'filters' => $request->only(['search', 'asset_category_id', 'asset_model_id', 'branch_id', 'asset_location_id', 'department_id', 'employee_id', 'status', 'condition', 'sort_by', 'sort_direction']),
        ]);
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $asset = Asset::create($request->validated());

        return response()->json([
            'message' => 'Asset created successfully',
            'data' => new AssetResource($asset),
        ], 201);
    }

    public function show(Asset $asset): AssetResource
    {
        return new AssetResource($asset->load(['category', 'model', 'branch', 'location', 'department', 'employee', 'supplier']));
    }

    public function profile(Asset $asset): Response
    {
        $asset->load([
            'category',
            'model',
            'branch',
            'location',
            'department',
            'employee',
            'supplier',
            'movements.fromBranch',
            'movements.toBranch',
            'movements.fromLocation',
            'movements.toLocation',
            'movements.fromEmployee',
            'movements.toEmployee',
            'movements.createdBy',
            'maintenances.supplier',
            'maintenances.createdBy',
            'stocktakeItems.stocktake.branch',
            'stocktakeItems.checkedBy',
            'depreciationLines.run.fiscalYear',
        ]);

        return Inertia::render('assets/profile', [
            'asset' => new AssetResource($asset),
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): JsonResponse
    {
        $data = UpdateAssetData::fromArray($request->validated());
        $asset->update($data->toArray());

        return response()->json([
            'message' => 'Asset updated successfully',
            'data' => new AssetResource($asset->fresh()),
        ]);
    }

    public function destroy(Asset $asset): JsonResponse
    {
        $asset->delete();

        return response()->json([
            'message' => 'Asset deleted successfully',
        ]);
    }

    public function export(ExportAssetRequest $request, ExportAssetsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
