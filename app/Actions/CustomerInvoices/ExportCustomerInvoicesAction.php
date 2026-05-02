<?php

namespace App\Actions\CustomerInvoices;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\CustomerInvoiceExport;
use App\Http\Requests\CustomerInvoices\ExportCustomerInvoiceRequest;
use Illuminate\Http\JsonResponse;

class ExportCustomerInvoicesAction
{
    use ConfiguredTransactionExportAction;

    public function execute(ExportCustomerInvoiceRequest $request): JsonResponse
    {
        return $this->exportWithConfiguration(
            'customer_invoices',
            $request,
            CustomerInvoiceExport::class
        );
    }
}
