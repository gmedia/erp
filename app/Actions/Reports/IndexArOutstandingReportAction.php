<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\BuildsCustomerInvoiceReportQuery;
use App\Actions\Reports\Concerns\HandlesReportQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexArOutstandingReportAction
{
    use BuildsCustomerInvoiceReportQuery;
    use HandlesReportQuery;

    public function execute(FormRequest $request): LengthAwarePaginator|Collection
    {
        $this->guardCustomerInvoiceCurrency($request, 'AR Outstanding Report');

        $query = $this->buildQuery();

        $this->applyBaseCustomerInvoiceFilters($request, $query);
        $this->applyOutstandingSorting($request, $query);

        return $this->exportOrPaginate($request, $query);
    }

    protected function buildQuery(): Builder
    {
        // days_overdue computed in PHP via Carbon (cross-DB). See ArOutstandingReportResource.
        return $this->buildBaseCustomerInvoiceQuery()
            ->withCasts($this->getBaseCasts());
    }

    protected function defaultSortBy(): string
    {
        return 'days_overdue';
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
        return [];
    }

    protected function fallbackSortBy(): string
    {
        return 'due_date';
    }

    /**
     * Resolves days_overdue sort to due_date with inverted direction:
     * "most overdue first" (desc) == "oldest due first" (asc).
     * All other sort keys delegate to the standard request sorting.
     */
    private function applyOutstandingSorting(FormRequest $request, Builder $query): void
    {
        $sortBy = $request->string('sort_by', $this->defaultSortBy())->toString();

        if ($sortBy === 'days_overdue') {
            $sortDirection = $request->string('sort_direction', 'desc')->toString();
            $invertedDirection = strtolower($sortDirection) === 'asc' ? 'desc' : 'asc';
            $query->orderBy('due_date', $invertedDirection);

            return;
        }

        $this->applyRequestSorting(
            $request,
            $query,
            $this->defaultSortBy(),
            $this->sortAliases(),
            $this->plainSortableColumns(),
            $this->aggregateSortableColumns(),
            $this->fallbackSortBy(),
        );
    }
}
