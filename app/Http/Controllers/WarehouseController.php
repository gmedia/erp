<?php

namespace App\Http\Controllers;

use App\Actions\Warehouses\ExportWarehousesAction;
use App\Actions\Warehouses\IndexWarehousesAction;
use App\Http\Requests\Warehouses\ExportWarehouseRequest;
use App\Http\Requests\Warehouses\IndexWarehouseRequest;
use App\Http\Requests\Warehouses\StoreWarehouseRequest;
use App\Http\Requests\Warehouses\UpdateWarehouseRequest;
use App\Http\Resources\Warehouses\WarehouseCollection;
use App\Http\Resources\Warehouses\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;

/**
 * Controller for warehouse management operations.
 *
 * Handles CRUD operations and export functionality for warehouses.
 */
class WarehouseController extends Controller
{
    /**
     * Display a listing of the warehouses.
     *
     * Supports pagination, search filtering, and sorting.
     */
    public function index(IndexWarehouseRequest $request): JsonResponse
    {
        $warehouses = (new IndexWarehousesAction())->execute($request);

        return (new WarehouseCollection($warehouses))->response();
    }

    /**
     * Store a newly created warehouse in storage.
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = Warehouse::create($request->validated());

        return (new WarehouseResource($warehouse))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified warehouse.
     */
    public function show(Warehouse $warehouse): JsonResponse
    {
        return (new WarehouseResource($warehouse))->response();
    }

    /**
     * Update the specified warehouse in storage.
     */
    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        $warehouse->update($request->validated());

        return (new WarehouseResource($warehouse))->response();
    }

    /**
     * Remove the specified warehouse from storage.
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $warehouse->delete();

        return response()->json(null, 204);
    }

    /**
     * Export warehouses to Excel based on filters.
     */
    public function export(ExportWarehouseRequest $request): JsonResponse
    {
        return (new ExportWarehousesAction())->execute($request);
    }
}
