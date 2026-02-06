<?php

namespace App\Http\Controllers;

use App\Actions\Suppliers\ExportSuppliersAction;
use App\Actions\Suppliers\IndexSuppliersAction;
use App\Domain\Suppliers\SupplierFilterService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Suppliers\ExportSupplierRequest;
use App\Http\Requests\Suppliers\IndexSupplierRequest;
use App\Http\Requests\Suppliers\StoreSupplierRequest;
use App\Http\Requests\Suppliers\UpdateSupplierRequest;
use App\Http\Resources\Suppliers\SupplierCollection;
use App\Http\Resources\Suppliers\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexSupplierRequest $request): JsonResponse
    {
        $suppliers = (new IndexSuppliersAction(app(SupplierFilterService::class)))->execute($request);

        return (new SupplierCollection($suppliers))->response();
    }

    /**
     * Export suppliers to Excel.
     *
     * @param  \App\Http\Requests\Suppliers\ExportSupplierRequest  $request
     * @param  \App\Actions\Suppliers\ExportSuppliersAction  $action
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(ExportSupplierRequest $request, ExportSuppliersAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = Supplier::create($request->validated());

        return (new SupplierResource($supplier))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier): JsonResponse
    {
        return (new SupplierResource($supplier))->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $supplier->update($request->validated());

        return (new SupplierResource($supplier))->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        $supplier->delete();

        return response()->json(null, 204);
    }
}
