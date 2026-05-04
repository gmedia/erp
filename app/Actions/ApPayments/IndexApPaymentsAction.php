<?php

namespace App\Actions\ApPayments;

use App\Actions\Concerns\ExecutesConfiguredMappedIndexRequest;
use App\Actions\Concerns\TransactionMappedIndexConfigurations;
use App\Domain\ApPayments\ApPaymentFilterService;
use App\Http\Requests\ApPayments\IndexApPaymentRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexApPaymentsAction
{
    use ExecutesConfiguredMappedIndexRequest;

    public function __construct(
        private ApPaymentFilterService $filterService
    ) {}

    public function execute(IndexApPaymentRequest $request): LengthAwarePaginator
    {
        return $this->executeConfiguredMappedIndexRequest(
            $request,
            $this->filterService,
            TransactionMappedIndexConfigurations::for('ap_payments'),
        );
    }
}
