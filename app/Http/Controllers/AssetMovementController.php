<?php

namespace App\Http\Controllers;

use App\Actions\AssetMovements\ExportAssetMovementsAction;
use App\Domain\AssetMovements\AssetMovementFilterService;
use App\Http\Requests\AssetMovements\ExportAssetMovementRequest;
use App\Http\Requests\AssetMovements\StoreAssetMovementRequest;
use App\Http\Requests\AssetMovements\UpdateAssetMovementRequest;
use App\Http\Resources\AssetMovements\AssetMovementResource;
use App\Models\Asset;
use App\Models\AssetMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

class AssetMovementController extends Controller
{
    public function index(Request $request, AssetMovementFilterService $filterService): Response|AnonymousResourceCollection
    {
        $query = AssetMovement::with([
            'asset',
            'fromBranch',
            'toBranch',
            'fromLocation',
            'toLocation',
            'fromDepartment',
            'toDepartment',
            'fromEmployee',
            'toEmployee',
            'createdBy'
        ]);

        if ($request->filled('search')) {
            $filterService->applySearch($query, $request->search, ['reference', 'notes', 'asset_name', 'asset_code']);
        } else {
            $filterService->applyAdvancedFilters($query, $request->all());
        }

        $filterService->applySorting(
            $query,
            $request->sort_by ?? 'moved_at',
            $request->sort_direction ?? 'desc',
            ['id', 'movement_type', 'moved_at', 'created_at']
        );

        $movements = $query->paginate($request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return AssetMovementResource::collection($movements);
        }

        return Inertia::render('asset-movements/index', [
            'movements' => AssetMovementResource::collection($movements),
            'filters' => $request->all(),
        ]);
    }

    public function show(AssetMovement $asset_movement): JsonResponse
    {
        return response()->json([
            'data' => new AssetMovementResource($asset_movement->load([
                'asset', 'fromBranch', 'toBranch', 'fromLocation', 'toLocation',
                'fromDepartment', 'toDepartment', 'fromEmployee', 'toEmployee', 'createdBy'
            ])),
        ]);
    }

    public function store(StoreAssetMovementRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $asset = Asset::findOrFail($request->asset_id);

            $movement = AssetMovement::create([
                ...$request->validated(),
                'from_branch_id' => $asset->branch_id,
                'from_location_id' => $asset->asset_location_id,
                'from_department_id' => $asset->department_id,
                'from_employee_id' => $asset->employee_id,
                'created_by' => Auth::id(),
            ]);

            // Update asset current state
            $asset->update([
                'branch_id' => $request->to_branch_id ?? $asset->branch_id,
                'asset_location_id' => $request->to_location_id ?? $asset->asset_location_id,
                'department_id' => $request->to_department_id ?? $asset->department_id,
                'employee_id' => $request->to_employee_id ?? $asset->employee_id,
            ]);

            return response()->json([
                'message' => 'Movement recorded successfully',
                'data' => new AssetMovementResource($movement->load([
                    'fromBranch', 'toBranch', 'fromLocation', 'toLocation',
                    'fromDepartment', 'toDepartment', 'fromEmployee', 'toEmployee', 'createdBy'
                ])),
            ], 201);
        });
    }

    public function update(UpdateAssetMovementRequest $request, AssetMovement $asset_movement): JsonResponse
    {
        $asset_movement->update($request->validated());

        return response()->json([
            'message' => 'Movement updated successfully',
            'data' => new AssetMovementResource($asset_movement->load([
                'asset', 'fromBranch', 'toBranch', 'fromLocation', 'toLocation',
                'fromDepartment', 'toDepartment', 'fromEmployee', 'toEmployee', 'createdBy'
            ])),
        ]);
    }

    public function destroy(AssetMovement $asset_movement): JsonResponse
    {
        return DB::transaction(function () use ($asset_movement) {
            $asset = $asset_movement->asset;

            // Find the latest movement for this asset
            $latestMovement = AssetMovement::where('asset_id', $asset->id)
                ->latest('moved_at')
                ->latest('id')
                ->first();

            // If we are deleting the latest movement, we should revert the asset state
            if ($latestMovement && $latestMovement->id === $asset_movement->id) {
                // Find the previous movement
                $previousMovement = AssetMovement::where('asset_id', $asset->id)
                    ->where('id', '!=', $asset_movement->id)
                    ->latest('moved_at')
                    ->latest('id')
                    ->first();

                if ($previousMovement) {
                    $asset->update([
                        'branch_id' => $previousMovement->to_branch_id,
                        'asset_location_id' => $previousMovement->to_location_id,
                        'department_id' => $previousMovement->to_department_id,
                        'employee_id' => $previousMovement->to_employee_id,
                    ]);
                } else {
                    // If no previous movement, revert to "initial" state if possible
                    // However, we don't have a record of the state before the first movement in the movements table
                    // unless we assume the 'from' fields of the first movement represent it.
                    $asset->update([
                        'branch_id' => $asset_movement->from_branch_id,
                        'asset_location_id' => $asset_movement->from_location_id,
                        'department_id' => $asset_movement->from_department_id,
                        'employee_id' => $asset_movement->from_employee_id,
                    ]);
                }
            }

            $asset_movement->delete();

            return response()->json([
                'message' => 'Movement deleted successfully',
            ]);
        });
    }

    public function export(ExportAssetMovementRequest $request, ExportAssetMovementsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
