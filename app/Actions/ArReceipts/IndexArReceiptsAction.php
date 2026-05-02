<?php

namespace App\Actions\ArReceipts;

use App\Actions\Concerns\ExecutesConfiguredMappedIndexRequest;
use App\Actions\Concerns\TransactionMappedIndexConfigurations;
use App\Domain\ArReceipts\ArReceiptFilterService;
use App\Http\Requests\ArReceipts\IndexArReceiptRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexArReceiptsAction
{
    use ExecutesConfiguredMappedIndexRequest;

    public function __construct(
        private ArReceiptFilterService $filterService
    ) {}

    public function execute(IndexArReceiptRequest $request): LengthAwarePaginator
    {
        return $this->executeConfiguredMappedIndexRequest(
            $request,
            $this->filterService,
            TransactionMappedIndexConfigurations::for('ar_receipts'),
        );
    }
}
