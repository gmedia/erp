<?php

namespace App\Actions\GoodsReceipts;

use App\Actions\Concerns\ExecutesConfiguredMappedIndexRequest;
use App\Actions\Concerns\TransactionMappedIndexConfigurations;
use App\Domain\GoodsReceipts\GoodsReceiptFilterService;
use App\Http\Requests\GoodsReceipts\IndexGoodsReceiptRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexGoodsReceiptsAction
{
    use ExecutesConfiguredMappedIndexRequest;

    public function __construct(
        private GoodsReceiptFilterService $filterService
    ) {}

    public function execute(IndexGoodsReceiptRequest $request): LengthAwarePaginator
    {
        return $this->executeConfiguredMappedIndexRequest(
            $request,
            $this->filterService,
            TransactionMappedIndexConfigurations::for('goods_receipts'),
        );
    }
}
