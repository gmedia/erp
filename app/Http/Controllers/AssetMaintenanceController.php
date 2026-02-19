<?php

namespace App\Http\Controllers;

use App\Actions\AssetMaintenances\{ExportAssetMaintenancesAction, IndexAssetMaintenancesAction};
use App\DTOs\AssetMaintenances\UpdateAssetMaintenanceData;
use App\Http\Requests\AssetMaintenances\{ExportAssetMaintenanceRequest, IndexAssetMaintenanceRequest, StoreAssetMaintenanceRequest, UpdateAssetMaintenanceRequest};
use App\Http\Resources\AssetMaintenances\{AssetMaintenanceCollection, AssetMaintenanceResource};
use App\Models\AssetMaintenance;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssetMaintenanceController extends Controller
{
    public function index(IndexAssetMaintenanceRequest $request, IndexAssetMaintenancesAction $action): Response|AssetMaintenanceCollection
    {
        $maintenances = $action->execute($request);

        if ($request->wantsJson()) {
            return new AssetMaintenanceCollection($maintenances);
        }

        return Inertia::render('asset-maintenances/index', [
            'asset_maintenances' => new AssetMaintenanceCollection($maintenances),
            'filters' => $request->only([
                'search',
                'asset_id',
                'maintenance_type',
                'status',
                'supplier_id',
                'created_by',
                'scheduled_from',
                'scheduled_to',
                'performed_from',
                'performed_to',
                'cost_min',
                'cost_max',
                'sort_by',
                'sort_direction',
            ]),
        ]);
    }

    public function store(StoreAssetMaintenanceRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! array_key_exists('cost', $data) || $data['cost'] === null) {
            $data['cost'] = 0;
        }

        $maintenance = AssetMaintenance::create([
            ...$data,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Asset maintenance created successfully',
            'data' => new AssetMaintenanceResource($maintenance->load(['asset', 'supplier', 'createdBy'])),
        ], 201);
    }

    public function show(AssetMaintenance $asset_maintenance): JsonResponse
    {
        return response()->json([
            'data' => new AssetMaintenanceResource($asset_maintenance->load(['asset', 'supplier', 'createdBy'])),
        ]);
    }

    public function update(UpdateAssetMaintenanceRequest $request, AssetMaintenance $asset_maintenance): JsonResponse
    {
        $dto = UpdateAssetMaintenanceData::fromArray($request->validated());

        $data = $dto->toArray();

        if (array_key_exists('cost', $data) && $data['cost'] === null) {
            $data['cost'] = 0;
        }

        $asset_maintenance->update($data);
        $asset_maintenance->refresh();

        return response()->json([
            'message' => 'Asset maintenance updated successfully',
            'data' => new AssetMaintenanceResource($asset_maintenance->load(['asset', 'supplier', 'createdBy'])),
        ]);
    }

    public function destroy(AssetMaintenance $asset_maintenance): JsonResponse
    {
        $asset_maintenance->delete();

        return response()->json([
            'message' => 'Asset maintenance deleted successfully',
        ]);
    }

    public function export(ExportAssetMaintenanceRequest $request, ExportAssetMaintenancesAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
