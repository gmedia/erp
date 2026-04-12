<?php

namespace App\Actions\PurchaseOrders;

use App\Actions\Concerns\ExecutesConfiguredMappedIndexRequest;
use App\Actions\Concerns\TransactionMappedIndexConfigurations;
use App\Domain\PurchaseOrders\PurchaseOrderFilterService;
use App\Http\Requests\PurchaseOrders\IndexPurchaseOrderRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexPurchaseOrdersAction
{
    use ExecutesConfiguredMappedIndexRequest;

    public function __construct(
        private PurchaseOrderFilterService $filterService
    ) {}

    public function execute(IndexPurchaseOrderRequest $request): LengthAwarePaginator
    {
        return $this->executeConfiguredMappedIndexRequest(
            $request,
            $this->filterService,
            TransactionMappedIndexConfigurations::for('purchase_orders'),
        );
    }
}
