<?php

namespace App\Http\Controllers;

use App\Actions\SupplierCategories\ExportSupplierCategoriesAction;
use App\Actions\SupplierCategories\IndexSupplierCategoriesAction;
use App\Http\Requests\SupplierCategories\ExportSupplierCategoryRequest;
use App\Http\Requests\SupplierCategories\IndexSupplierCategoryRequest;
use App\Http\Requests\SupplierCategories\StoreSupplierCategoryRequest;
use App\Http\Requests\SupplierCategories\UpdateSupplierCategoryRequest;
use App\Http\Resources\SupplierCategories\SupplierCategoryCollection;
use App\Http\Resources\SupplierCategories\SupplierCategoryResource;
use App\Models\SupplierCategory;
use Illuminate\Http\JsonResponse;

/**
 * Controller for supplier category management operations.
 *
 * Handles CRUD operations and export functionality for supplier categories.
 */
class SupplierCategoryController extends Controller
{
    /**
     * Display a listing of the supplier categories.
     *
     * Supports pagination, search filtering, and sorting.
     */
    public function index(IndexSupplierCategoryRequest $request): JsonResponse
    {
        $supplierCategories = (new IndexSupplierCategoriesAction())->execute($request);

        return (new SupplierCategoryCollection($supplierCategories))->response();
    }

    /**
     * Store a newly created supplier category in storage.
     */
    public function store(StoreSupplierCategoryRequest $request): JsonResponse
    {
        $supplierCategory = SupplierCategory::create($request->validated());

        return (new SupplierCategoryResource($supplierCategory))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified supplier category.
     */
    public function show(SupplierCategory $supplierCategory): JsonResponse
    {
        return (new SupplierCategoryResource($supplierCategory))->response();
    }

    /**
     * Update the specified supplier category in storage.
     */
    public function update(UpdateSupplierCategoryRequest $request, SupplierCategory $supplierCategory): JsonResponse
    {
        $supplierCategory->update($request->validated());

        return (new SupplierCategoryResource($supplierCategory))->response();
    }

    /**
     * Remove the specified supplier category from storage.
     */
    public function destroy(SupplierCategory $supplierCategory): JsonResponse
    {
        $supplierCategory->delete();

        return response()->json(null, 204);
    }

    /**
     * Export supplier categories to Excel based on filters.
     */
    public function export(ExportSupplierCategoryRequest $request): JsonResponse
    {
        return (new ExportSupplierCategoriesAction())->execute($request);
    }
}
