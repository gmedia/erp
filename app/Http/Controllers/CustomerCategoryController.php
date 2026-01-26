<?php

namespace App\Http\Controllers;

use App\Actions\CustomerCategories\ExportCustomerCategoriesAction;
use App\Actions\CustomerCategories\IndexCustomerCategoriesAction;
use App\Domain\CustomerCategories\CustomerCategoryFilterService;
use App\Http\Requests\CustomerCategories\ExportCustomerCategoryRequest;
use App\Http\Requests\CustomerCategories\IndexCustomerCategoryRequest;
use App\Http\Requests\CustomerCategories\StoreCustomerCategoryRequest;
use App\Http\Requests\CustomerCategories\UpdateCustomerCategoryRequest;
use App\Http\Resources\CustomerCategories\CustomerCategoryCollection;
use App\Http\Resources\CustomerCategories\CustomerCategoryResource;
use App\Models\CustomerCategory;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerCategoryController extends Controller
{
    /**
     * Display a listing of the customer categories.
     *
     * @param  \App\Http\Requests\CustomerCategories\IndexCustomerCategoryRequest  $request
     * @return \Inertia\Response
     */
    public function index(IndexCustomerCategoryRequest $request): Response
    {
        $categories = (new IndexCustomerCategoriesAction(app(CustomerCategoryFilterService::class)))->execute($request);

        return Inertia::render('customer-categories/index', [
            'categories' => new CustomerCategoryCollection($categories),
            'filters' => $request->only(['search', 'sort_by', 'sort_direction']),
        ]);
    }

    /**
     * Store a newly created customer category in storage.
     *
     * @param  \App\Http\Requests\CustomerCategories\StoreCustomerCategoryRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreCustomerCategoryRequest $request): JsonResponse
    {
        $category = CustomerCategory::create($request->validated());

        return (new CustomerCategoryResource($category))->response()->setStatusCode(201);
    }

    /**
     * Display the specified customer category.
     *
     * @param  \App\Models\CustomerCategory  $customerCategory
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(CustomerCategory $customerCategory): JsonResponse
    {
        return (new CustomerCategoryResource($customerCategory))->response();
    }

    /**
     * Update the specified customer category in storage.
     *
     * @param  \App\Http\Requests\CustomerCategories\UpdateCustomerCategoryRequest  $request
     * @param  \App\Models\CustomerCategory  $customerCategory
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateCustomerCategoryRequest $request, CustomerCategory $customerCategory): JsonResponse
    {
        $customerCategory->update($request->validated());

        return (new CustomerCategoryResource($customerCategory))->response();
    }

    /**
     * Remove the specified customer category from storage.
     *
     * @param  \App\Models\CustomerCategory  $customerCategory
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(CustomerCategory $customerCategory): JsonResponse
    {
        $customerCategory->delete();

        return response()->json(null, 204);
    }

    /**
     * Export customer categories to Excel based on filters.
     *
     * @param  \App\Http\Requests\CustomerCategories\ExportCustomerCategoryRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(ExportCustomerCategoryRequest $request): JsonResponse
    {
        return (new ExportCustomerCategoriesAction(app(CustomerCategoryFilterService::class)))->execute($request);
    }
}
