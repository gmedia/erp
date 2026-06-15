<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\AgingReportBoundaries;
use App\Actions\Reports\Concerns\BuildsCustomerInvoiceReportQuery;
use App\Actions\Reports\Concerns\HandlesReportQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexArAgingReportAction
{
    use AgingReportBoundaries;
    use BuildsCustomerInvoiceReportQuery;
    use HandlesReportQuery;

    public function execute(FormRequest $request): LengthAwarePaginator|Collection
    {
        $query = $this->buildQuery();

        $this->applyBaseCustomerInvoiceFilters($request, $query);
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

    protected function buildQuery(): Builder
    {
        $boundaries = $this->agingBoundaries();

        return $this->buildBaseCustomerInvoiceQuery(
            [$this->agingBucketSelectSql('ci')],
            $this->agingBucketBindings($boundaries),
        )->withCasts(array_merge($this->getBaseCasts(), [
            'aging_current' => 'decimal:2',
            'aging_1_30' => 'decimal:2',
            'aging_31_60' => 'decimal:2',
            'aging_61_90' => 'decimal:2',
            'aging_over_90' => 'decimal:2',
        ]));
    }

    protected function defaultSortBy(): string
    {
        return 'invoice_date';
    }

    protected function sortAliases(): array
    {
        return $this->getBaseSortAliases();
    }

    protected function plainSortableColumns(): array
    {
        return $this->getBasePlainSortableColumns();
    }

    protected function aggregateSortableColumns(): array
    {
        return [
            'aging_current',
            'aging_1_30',
            'aging_31_60',
            'aging_61_90',
            'aging_over_90',
        ];
    }

    protected function fallbackSortBy(): string
    {
        return $this->defaultSortBy();
    }
}
