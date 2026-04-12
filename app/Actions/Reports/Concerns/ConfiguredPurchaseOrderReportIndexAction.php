<?php

namespace App\Actions\Reports\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

abstract class ConfiguredPurchaseOrderReportIndexAction
{
    use HandlesReportQuery;

    public function execute(FormRequest $request): LengthAwarePaginator|Collection
    {
        $query = $this->buildQuery();

        $this->applyPurchaseOrderReportFilters(
            $request,
            $query,
            $this->warehouseColumn(),
            $this->productColumn(),
            $this->statusColumn(),
            $this->dateColumn(),
            $this->searchColumns(),
        );

        $this->applyAdditionalFilters($request, $query);

        $this->applyRequestSorting(
            $request,
            $query,
            $this->defaultSortBy(),
            $this->sortAliases(),
            $this->plainSortableColumns(),
            $this->aggregateSortableColumns(),
            $this->fallbackSortBy(),
        );

        return $this->exportOrPaginate($request, $query);
    }

    abstract protected function buildQuery(): Builder;

    protected function warehouseColumn(): string
    {
        return 'po.warehouse_id';
    }

    protected function productColumn(): string
    {
        return 'poi.product_id';
    }

    protected function statusColumn(): string
    {
        return 'po.status';
    }

    protected function dateColumn(): string
    {
        return 'po.order_date';
    }

    /**
     * @return array<int, string>
     */
    protected function searchColumns(): array
    {
        return $this->basePurchaseOrderReportSearchColumns();
    }

    protected function defaultSortBy(): string
    {
        return 'order_date';
    }

    /**
     * @return array<string, string>
     */
    abstract protected function sortAliases(): array;

    /**
     * @return array<int, string>
     */
    abstract protected function plainSortableColumns(): array;

    /**
     * @return array<int, string>
     */
    abstract protected function aggregateSortableColumns(): array;

    protected function fallbackSortBy(): string
    {
        return $this->defaultSortBy();
    }

    protected function applyAdditionalFilters(FormRequest $request, Builder $query): void
    {
        // Intentionally empty. Actions can override this when they need extra filters.
    }

    /**
     * @return array<int, string>
     */
    protected function purchaseOrderPartySelectColumns(): array
    {
        return [
            's.id as supplier_id',
            's.name as supplier_name',
            'w.id as warehouse_id',
            'w.code as warehouse_code',
            'w.name as warehouse_name',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function purchaseOrderPartyGroupByColumns(): array
    {
        return [
            's.id',
            's.name',
            'w.id',
            'w.code',
            'w.name',
        ];
    }

    protected function joinSupplierAndWarehouseTables(
        Builder $query,
        string $supplierIdColumn,
        string $warehouseIdColumn,
    ): Builder {
        return $query
            ->join('suppliers as s', $supplierIdColumn, '=', 's.id')
            ->join('warehouses as w', $warehouseIdColumn, '=', 'w.id');
    }

    protected function joinProductDimensionTables(
        Builder $query,
        string $itemTable,
        string $itemLeftColumn,
        string $itemRightColumn,
        string $productIdColumn,
    ): Builder {
        return $query
            ->leftJoin($itemTable, $itemLeftColumn, '=', $itemRightColumn)
            ->leftJoin('products as p', $productIdColumn, '=', 'p.id');
    }

    /**
     * @return array<int, string>
     */
    protected function basePurchaseOrderReportSearchColumns(): array
    {
        return [
            'po.po_number',
            's.name',
            'w.name',
            'w.code',
            'p.name',
            'p.code',
        ];
    }

    /**
     * @param  array<string, string>  $aliases
     * @return array<string, string>
     */
    protected function purchaseOrderSortAliasMap(array $aliases = []): array
    {
        return array_merge([
            'purchase_order_po_number' => 'po_number',
            'purchase_order_order_date' => 'order_date',
            'purchase_order_expected_delivery_date' => 'expected_delivery_date',
            'purchase_order_status' => 'status',
        ], $aliases);
    }

    /**
     * @param  array<int, string>  $selectColumns
     * @param  array<int, string>  $metricColumns
     */
    protected function compilePurchaseOrderSummarySelect(array $selectColumns, array $metricColumns): string
    {
        return $this->compileSelectColumns([
            ...$selectColumns,
            ...$this->purchaseOrderPartySelectColumns(),
            ...$metricColumns,
        ]);
    }

    /**
     * @param  array<int, string>  $groupByColumns
     * @return array<int, string>
     */
    protected function purchaseOrderGroupedColumns(array $groupByColumns): array
    {
        return [
            ...$groupByColumns,
            ...$this->purchaseOrderPartyGroupByColumns(),
        ];
    }

    /**
     * @param  array<int, string>  $extraColumns
     * @return array<int, string>
     */
    protected function purchaseOrderPlainSortableColumns(array $extraColumns = []): array
    {
        return array_merge([
            'po_number',
            'supplier_name',
            'warehouse_name',
            'order_date',
            'expected_delivery_date',
            'status',
        ], $extraColumns);
    }

    /**
     * @param  array<int, string>  $extraColumns
     * @return array<int, string>
     */
    protected function purchaseOrderAggregateSortableColumns(array $extraColumns = []): array
    {
        return array_merge([
            'ordered_quantity',
            'received_quantity',
            'outstanding_quantity',
        ], $extraColumns);
    }

    /**
     * @param  array<string, string>  $extraCasts
     * @return array<string, string>
     */
    protected function purchaseOrderQuantityCasts(array $extraCasts = []): array
    {
        return array_merge([
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'ordered_quantity' => 'decimal:2',
            'received_quantity' => 'decimal:2',
            'outstanding_quantity' => 'decimal:2',
        ], $extraCasts);
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function compileSelectColumns(array $columns): string
    {
        return implode(",\n                ", $columns);
    }
}
