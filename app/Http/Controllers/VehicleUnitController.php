<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleUnits\StoreVehicleUnitRequest;
use App\Http\Requests\VehicleUnits\UpdateVehicleUnitRequest;
use App\Http\Resources\VehicleUnits\VehicleUnitResource;
use App\Models\VehicleUnit;
use Illuminate\Http\JsonResponse;

class VehicleUnitController extends Controller
{
    public function index(): JsonResponse
    {
        $units = VehicleUnit::query()
            ->with('asset')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($units);
    }

    public function store(StoreVehicleUnitRequest $request): JsonResponse
    {
        $unit = VehicleUnit::create($request->validated());

        return (new VehicleUnitResource($unit))->response()->setStatusCode(201);
    }

    public function show(VehicleUnit $vehicleUnit): JsonResponse
    {
        return (new VehicleUnitResource($vehicleUnit->load('asset')))->response();
    }

    public function update(UpdateVehicleUnitRequest $request, VehicleUnit $vehicleUnit): JsonResponse
    {
        $vehicleUnit->update($request->validated());

        return (new VehicleUnitResource($vehicleUnit))->response();
    }

    public function destroy(VehicleUnit $vehicleUnit): JsonResponse
    {
        return $this->destroyModel($vehicleUnit);
    }
}
