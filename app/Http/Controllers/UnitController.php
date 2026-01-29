<?php

namespace App\Http\Controllers;

use App\Actions\Units\ExportUnitsAction;
use App\Actions\Units\IndexUnitsAction;
use App\Http\Requests\Units\ExportUnitRequest;
use App\Http\Requests\Units\IndexUnitRequest;
use App\Http\Requests\Units\StoreUnitRequest;
use App\Http\Requests\Units\UpdateUnitRequest;
use App\Http\Resources\Units\UnitCollection;
use App\Http\Resources\Units\UnitResource;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;

/**
 * Controller for unit management operations.
 *
 * Handles CRUD operations and export functionality for units.
 */
class UnitController extends Controller
{
    /**
     * Display a listing of the units.
     *
     * Supports pagination, search filtering, and sorting.
     */
    public function index(IndexUnitRequest $request): JsonResponse
    {
        $units = (new IndexUnitsAction())->execute($request);

        return (new UnitCollection($units))->response();
    }

    /**
     * Store a newly created unit in storage.
     */
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $unit = Unit::create($request->validated());

        return (new UnitResource($unit))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified unit.
     */
    public function show(Unit $unit): JsonResponse
    {
        return (new UnitResource($unit))->response();
    }

    /**
     * Update the specified unit in storage.
     */
    public function update(UpdateUnitRequest $request, Unit $unit): JsonResponse
    {
        $unit->update($request->validated());

        return (new UnitResource($unit))->response();
    }

    /**
     * Remove the specified unit from storage.
     */
    public function destroy(Unit $unit): JsonResponse
    {
        $unit->delete();

        return response()->json(null, 204);
    }

    /**
     * Export units to Excel based on filters.
     */
    public function export(ExportUnitRequest $request): JsonResponse
    {
        return (new ExportUnitsAction())->execute($request);
    }
}
