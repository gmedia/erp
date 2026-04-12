<?php

namespace App\Actions\PurchaseRequests;

use App\Actions\Concerns\ExecutesConfiguredMappedIndexRequest;
use App\Actions\Concerns\TransactionMappedIndexConfigurations;
use App\Domain\PurchaseRequests\PurchaseRequestFilterService;
use App\Http\Requests\PurchaseRequests\IndexPurchaseRequestRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexPurchaseRequestsAction
{
    use ExecutesConfiguredMappedIndexRequest;

    public function __construct(
        private PurchaseRequestFilterService $filterService
    ) {}

    public function execute(IndexPurchaseRequestRequest $request): LengthAwarePaginator
    {
        return $this->executeConfiguredMappedIndexRequest(
            $request,
            $this->filterService,
            TransactionMappedIndexConfigurations::for('purchase_requests'),
        );
    }
}
