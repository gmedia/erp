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
        return $this->buildBaseCustomerInvoiceQuery([$this->daysOverdueSelectSql()])
            ->withCasts(array_merge($this->getBaseCasts(), [
                'days_overdue' => 'integer',
            ]));
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
        return [
            'days_overdue',
        ];
    }

    protected function fallbackSortBy(): string
    {
        return $this->defaultSortBy();
    }

    private function daysOverdueSelectSql(): string
    {
        return "CASE
            WHEN ci.status IN ('sent', 'partially_paid', 'overdue') AND ci.due_date < CURDATE()
            THEN DATEDIFF(CURDATE(), ci.due_date)
            ELSE 0
        END as days_overdue";
    }
}
