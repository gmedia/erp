<?php

namespace App\Http\Controllers;

use App\Http\Requests\Assets\StoreAssetMovementRequest;
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
    public function index(Request $request): Response|AnonymousResourceCollection
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
        ])->latest('moved_at');

        if ($request->has('asset_id')) {
            $query->where('asset_id', $request->asset_id);
        }

        $movements = $query->paginate($request->integer('per_page', 15));

        if ($request->wantsJson()) {
            return AssetMovementResource::collection($movements);
        }

        return Inertia::render('assets/movements/index', [
            'movements' => AssetMovementResource::collection($movements),
            'filters' => $request->only(['asset_id']),
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
}
