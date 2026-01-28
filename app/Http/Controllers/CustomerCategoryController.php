<?php

namespace App\Http\Controllers;

use App\Actions\CustomerCategories\ExportCustomerCategoriesAction;
use App\Actions\CustomerCategories\IndexCustomerCategoriesAction;
use App\Http\Requests\CustomerCategories\ExportCustomerCategoryRequest;
use App\Http\Requests\CustomerCategories\IndexCustomerCategoryRequest;
use App\Http\Requests\CustomerCategories\StoreCustomerCategoryRequest;
use App\Http\Requests\CustomerCategories\UpdateCustomerCategoryRequest;
use App\Http\Resources\CustomerCategories\CustomerCategoryCollection;
use App\Http\Resources\CustomerCategories\CustomerCategoryResource;
use App\Models\CustomerCategory;
use Illuminate\Http\JsonResponse;

/**
 * Controller for customer category management operations.
 *
 * Handles CRUD operations and export functionality for customer categories.
 */
class CustomerCategoryController extends Controller
{
    /**
     * Display a listing of the customer categories.
     *
     * Supports pagination, search filtering, and sorting.
     */
    public function index(IndexCustomerCategoryRequest $request): JsonResponse
    {
        $customerCategories = (new IndexCustomerCategoriesAction())->execute($request);

        return (new CustomerCategoryCollection($customerCategories))->response();
    }

    /**
     * Store a newly created customer category in storage.
     */
    public function store(StoreCustomerCategoryRequest $request): JsonResponse
    {
        $customerCategory = CustomerCategory::create($request->validated());

        return (new CustomerCategoryResource($customerCategory))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified customer category.
     */
    public function show(CustomerCategory $customerCategory): JsonResponse
    {
        return (new CustomerCategoryResource($customerCategory))->response();
    }

    /**
     * Update the specified customer category in storage.
     */
    public function update(UpdateCustomerCategoryRequest $request, CustomerCategory $customerCategory): JsonResponse
    {
        $customerCategory->update($request->validated());

        return (new CustomerCategoryResource($customerCategory))->response();
    }

    /**
     * Remove the specified customer category from storage.
     */
    public function destroy(CustomerCategory $customerCategory): JsonResponse
    {
        $customerCategory->delete();

        return response()->json(null, 204);
    }

    /**
     * Export customer categories to Excel based on filters.
     */
    public function export(ExportCustomerCategoryRequest $request): JsonResponse
    {
        return (new ExportCustomerCategoriesAction())->execute($request);
    }
}
