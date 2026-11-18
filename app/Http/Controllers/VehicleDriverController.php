<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleDrivers\StoreVehicleDriverRequest;
use App\Http\Requests\VehicleDrivers\UpdateVehicleDriverRequest;
use App\Http\Resources\VehicleDrivers\VehicleDriverResource;
use App\Models\VehicleDriver;
use Illuminate\Http\JsonResponse;

class VehicleDriverController extends Controller
{
    public function index(): JsonResponse
    {
        $drivers = VehicleDriver::query()
            ->with('employee')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($drivers);
    }

    public function store(StoreVehicleDriverRequest $request): JsonResponse
    {
        $driver = VehicleDriver::create($request->validated());

        return (new VehicleDriverResource($driver))->response()->setStatusCode(201);
    }

    public function show(VehicleDriver $vehicleDriver): JsonResponse
    {
        return (new VehicleDriverResource($vehicleDriver->load('employee')))->response();
    }

    public function update(UpdateVehicleDriverRequest $request, VehicleDriver $vehicleDriver): JsonResponse
    {
        $vehicleDriver->update($request->validated());

        return (new VehicleDriverResource($vehicleDriver))->response();
    }

    public function destroy(VehicleDriver $vehicleDriver): JsonResponse
    {
        return $this->destroyModel($vehicleDriver);
    }
}
