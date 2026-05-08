<?php

namespace App\Actions\SupplierBills;

use App\Actions\Concerns\ExecutesConfiguredMappedIndexRequest;
use App\Actions\Concerns\TransactionMappedIndexConfigurations;
use App\Domain\SupplierBills\SupplierBillFilterService;
use App\Http\Requests\SupplierBills\IndexSupplierBillRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexSupplierBillsAction
{
    use ExecutesConfiguredMappedIndexRequest;

    public function __construct(
        private SupplierBillFilterService $filterService
    ) {}

    public function execute(IndexSupplierBillRequest $request): LengthAwarePaginator
    {
        return $this->executeConfiguredMappedIndexRequest(
            $request,
            $this->filterService,
            TransactionMappedIndexConfigurations::for('supplier_bills'),
        );
    }
}
