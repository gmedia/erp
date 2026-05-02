<?php

namespace App\Actions\ArReceipts;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\ArReceiptExport;
use App\Http\Requests\ArReceipts\ExportArReceiptRequest;
use Illuminate\Http\JsonResponse;

class ExportArReceiptsAction
{
    use ConfiguredTransactionExportAction;

    public function execute(ExportArReceiptRequest $request): JsonResponse
    {
        return $this->exportWithConfiguration(
            'ar_receipts',
            $request,
            ArReceiptExport::class
        );
    }
}
