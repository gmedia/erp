<?php

namespace App\Actions\CustomerInvoices;

use App\Actions\Concerns\ExecutesConfiguredMappedIndexRequest;
use App\Actions\Concerns\TransactionMappedIndexConfigurations;
use App\Domain\CustomerInvoices\CustomerInvoiceFilterService;
use App\Http\Requests\CustomerInvoices\IndexCustomerInvoiceRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexCustomerInvoicesAction
{
    use ExecutesConfiguredMappedIndexRequest;

    public function __construct(
        private CustomerInvoiceFilterService $filterService
    ) {}

    public function execute(IndexCustomerInvoiceRequest $request): LengthAwarePaginator
    {
        return $this->executeConfiguredMappedIndexRequest(
            $request,
            $this->filterService,
            TransactionMappedIndexConfigurations::for('customer_invoices'),
        );
    }
}
