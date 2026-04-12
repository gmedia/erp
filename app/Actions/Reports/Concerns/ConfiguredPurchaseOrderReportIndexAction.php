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

    abstract protected function warehouseColumn(): string;

    abstract protected function productColumn(): string;

    abstract protected function statusColumn(): string;

    abstract protected function dateColumn(): string;

    /**
     * @return array<int, string>
     */
    abstract protected function searchColumns(): array;

    abstract protected function defaultSortBy(): string;

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
     * @param  array<int, string>  $columns
     */
    protected function compileSelectColumns(array $columns): string
    {
        return implode(",\n                ", $columns);
    }
}
