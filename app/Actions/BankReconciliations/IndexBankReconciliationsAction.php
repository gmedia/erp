<?php

namespace App\Actions\BankReconciliations;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\BankReconciliations\BankReconciliationFilterService;
use App\Http\Requests\BankReconciliations\IndexBankReconciliationRequest;
use App\Models\BankReconciliation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexBankReconciliationsAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private BankReconciliationFilterService $filterService,
    ) {}

    public function execute(IndexBankReconciliationRequest $request): LengthAwarePaginator
    {
        $query = BankReconciliation::query()->with([
            'account',
            'fiscalYear',
            'completedBy',
            'creator',
        ]);

        return $this->handleIndexRequest(
            $request,
            $query,
            $this->filterService,
            ['notes'],
            ['status', 'account_id', 'fiscal_year_id', 'date_from', 'date_to'],
            'reconciliation_date',
            [
                'account_id',
                'reconciliation_date',
                'period_start',
                'period_end',
                'statement_balance',
                'book_balance',
                'difference',
                'status',
                'created_at',
            ],
        );
    }
}
