<?php

namespace App\Http\Controllers;

use App\Actions\Products\ExportProductsAction;
use App\Actions\Products\IndexProductsAction;
use App\Domain\Products\ProductFilterService;
use App\Http\Requests\Products\ExportProductRequest;
use App\Http\Requests\Products\IndexProductRequest;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\Products\ProductCollection;
use App\Http\Resources\Products\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

/**
 * Controller for product and service management operations.
 */
class ProductController extends Controller
{
    /**
     * Display a listing of the products with filtering and sorting.
     */
    public function index(IndexProductRequest $request): JsonResponse
    {
        $products = (new IndexProductsAction(app(ProductFilterService::class)))->execute($request);

        return (new ProductCollection($products))->response();
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): JsonResponse
    {
        return (new ProductResource($product))->response();
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return (new ProductResource($product))->response();
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }

    /**
     * Export products to Excel based on filters.
     */
    public function export(ExportProductRequest $request): JsonResponse
    {
        return (new ExportProductsAction())->execute($request);
    }
}
