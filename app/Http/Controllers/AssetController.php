<?php

namespace App\Http\Controllers;

use App\Actions\Assets\ExportAssetsAction;
use App\Actions\Assets\ImportAssetsAction;
use App\Actions\Assets\IndexAssetsAction;
use App\DTOs\Assets\UpdateAssetData;
use App\Http\Requests\Assets\ExportAssetRequest;
use App\Http\Requests\Assets\ImportAssetRequest;
use App\Http\Requests\Assets\IndexAssetRequest;
use App\Http\Requests\Assets\StoreAssetRequest;
use App\Http\Requests\Assets\UpdateAssetRequest;
use App\Http\Resources\Assets\AssetCollection;
use App\Http\Resources\Assets\AssetResource;
use App\Models\Asset;
use App\Models\AssetMovement;
use Illuminate\Http\JsonResponse;

class AssetController extends Controller
{
    public function index(IndexAssetRequest $request, IndexAssetsAction $action): AssetCollection
    {
        $assets = $action->execute($request);

        return new AssetCollection($assets);
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $asset = Asset::create($request->validated());

        AssetMovement::create([
            'asset_id' => $asset->id,
            'movement_type' => 'acquired',
            'moved_at' => $asset->purchase_date,
            'to_branch_id' => $asset->branch_id,
            'to_location_id' => $asset->asset_location_id,
            'to_department_id' => $asset->department_id,
            'to_employee_id' => $asset->employee_id,
            'notes' => 'Initial acquisition',
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Asset created successfully',
            'data' => new AssetResource($asset),
        ], 201);
    }

    public function show(Asset $asset): AssetResource
    {
        return new AssetResource($asset->load(['category', 'model', 'branch', 'location', 'department', 'employee', 'supplier']));
    }

    public function update(UpdateAssetRequest $request, Asset $asset): JsonResponse
    {
        $data = UpdateAssetData::fromArray($request->validated());
        $asset->update($data->toArray());

        $asset->refresh();

        AssetMovement::updateOrCreate(
            [
                'asset_id' => $asset->id,
                'movement_type' => 'acquired',
            ],
            [
                'moved_at' => $asset->purchase_date,
                'to_branch_id' => $asset->branch_id,
                'to_location_id' => $asset->asset_location_id,
                'to_department_id' => $asset->department_id,
                'to_employee_id' => $asset->employee_id,
                'notes' => 'Initial acquisition (synced)',
                'created_by' => $request->user()->id,
            ]
        );

        return response()->json([
            'message' => 'Asset updated successfully',
            'data' => new AssetResource($asset),
        ]);
    }

    public function destroy(Asset $asset): JsonResponse
    {
        $asset->delete();

        return response()->json([
            'message' => 'Asset deleted successfully',
        ]);
    }

    public function profile(Asset $asset): JsonResponse
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

        return response()->json([
            'asset' => [
                'data' => new AssetResource($asset),
            ],
        ]);
    }

    public function export(ExportAssetRequest $request, ExportAssetsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    public function import(ImportAssetRequest $request, ImportAssetsAction $action): JsonResponse
    {
        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        $summary = $action->execute($file);

        return response()->json($summary);
    }
}
