<?php

namespace App\Actions\SupplierReturns;

use App\Actions\Concerns\ExecutesConfiguredMappedIndexRequest;
use App\Actions\Concerns\TransactionMappedIndexConfigurations;
use App\Domain\SupplierReturns\SupplierReturnFilterService;
use App\Http\Requests\SupplierReturns\IndexSupplierReturnRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexSupplierReturnsAction
{
    use ExecutesConfiguredMappedIndexRequest;

    public function __construct(
        private SupplierReturnFilterService $filterService
    ) {}

    public function execute(IndexSupplierReturnRequest $request): LengthAwarePaginator
    {
        return $this->executeConfiguredMappedIndexRequest(
            $request,
            $this->filterService,
            TransactionMappedIndexConfigurations::for('supplier_returns'),
        );
    }
}
