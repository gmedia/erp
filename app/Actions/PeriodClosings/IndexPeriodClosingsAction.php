<?php

namespace App\Actions\PeriodClosings;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\PeriodClosings\PeriodClosingFilterService;
use App\Http\Requests\PeriodClosings\IndexPeriodClosingRequest;
use App\Models\PeriodClosing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexPeriodClosingsAction
{
    use InteractsWithIndexRequest;

    public function __construct(private PeriodClosingFilterService $filterService) {}

    public function execute(IndexPeriodClosingRequest $request): LengthAwarePaginator
    {
        $query = PeriodClosing::query()->with(['fiscalYear', 'closingJournalEntry', 'retainedEarningsAccount', 'closedBy', 'reopenedBy', 'creator']);

        return $this->handleIndexRequest($request, $query, $this->filterService, ['notes'], ['status', 'closing_type', 'fiscal_year_id', 'period_year', 'period_month'], 'period_year', ['period_year', 'period_month', 'closing_type', 'status', 'net_income', 'created_at']);
    }
}
