<?php

namespace App\Http\Controllers;

use App\Actions\StockMonitor\ExportStockMonitorAction;
use App\Actions\StockMonitor\IndexStockMonitorAction;
use App\Http\Requests\StockMonitor\ExportStockMonitorRequest;
use App\Http\Requests\StockMonitor\IndexStockMonitorRequest;
use App\Http\Resources\StockMonitor\StockMonitorCollection;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class StockMonitorController extends Controller
{
    public function index(IndexStockMonitorRequest $request, IndexStockMonitorAction $action): Response|StockMonitorCollection
    {
        $result = $action->execute($request);
        $stocks = $result['stocks'];
        $summary = $result['summary'];

        if ($request->wantsJson()) {
            return new StockMonitorCollection($stocks, $summary);
        }

        return Inertia::render('stock-monitor/index', [
            'stocks' => new StockMonitorCollection($stocks, $summary),
            'filters' => $request->only([
                'search',
                'product_id',
                'warehouse_id',
                'branch_id',
                'category_id',
                'low_stock_threshold',
                'sort_by',
                'sort_direction',
                'per_page',
            ]),
            'filterOptions' => [
                'products' => Product::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'code'])
                    ->map(fn (Product $product) => [
                        'value' => (string) $product->id,
                        'label' => trim($product->code . ' - ' . $product->name, ' -'),
                    ]),
                'warehouses' => Warehouse::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'code'])
                    ->map(fn (Warehouse $warehouse) => [
                        'value' => (string) $warehouse->id,
                        'label' => trim($warehouse->code . ' - ' . $warehouse->name, ' -'),
                    ]),
                'branches' => Branch::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Branch $branch) => [
                        'value' => (string) $branch->id,
                        'label' => $branch->name,
                    ]),
                'categories' => ProductCategory::query()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (ProductCategory $category) => [
                        'value' => (string) $category->id,
                        'label' => $category->name,
                    ]),
            ],
        ]);
    }

    public function export(ExportStockMonitorRequest $request, ExportStockMonitorAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
