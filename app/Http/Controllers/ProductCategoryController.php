<?php

namespace App\Http\Controllers;

use App\Actions\ProductCategories\ExportProductCategoriesAction;
use App\Actions\ProductCategories\IndexProductCategoriesAction;
use App\Http\Requests\ProductCategories\ExportProductCategoryRequest;
use App\Http\Requests\ProductCategories\IndexProductCategoryRequest;
use App\Http\Requests\ProductCategories\StoreProductCategoryRequest;
use App\Http\Requests\ProductCategories\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategories\ProductCategoryCollection;
use App\Http\Resources\ProductCategories\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;

/**
 * Controller for product category management operations.
 *
 * Handles CRUD operations and export functionality for product categories.
 */
class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the product categories.
     *
     * Supports pagination, search filtering, and sorting.
     */
    public function index(IndexProductCategoryRequest $request): JsonResponse
    {
        $productCategories = (new IndexProductCategoriesAction())->execute($request);

        return (new ProductCategoryCollection($productCategories))->response();
    }

    /**
     * Store a newly created product category in storage.
     */
    public function store(StoreProductCategoryRequest $request): JsonResponse
    {
        $productCategory = ProductCategory::create($request->validated());

        return (new ProductCategoryResource($productCategory))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified product category.
     */
    public function show(ProductCategory $productCategory): JsonResponse
    {
        return (new ProductCategoryResource($productCategory))->response();
    }

    /**
     * Update the specified product category in storage.
     */
    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): JsonResponse
    {
        $productCategory->update($request->validated());

        return (new ProductCategoryResource($productCategory))->response();
    }

    /**
     * Remove the specified product category from storage.
     */
    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        $productCategory->delete();

        return response()->json(null, 204);
    }

    /**
     * Export product categories to Excel based on filters.
     */
    public function export(ExportProductCategoryRequest $request): JsonResponse
    {
        return (new ExportProductCategoriesAction())->execute($request);
    }
}
