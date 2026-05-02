<?php

namespace App\Actions\CreditNotes;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\CreditNoteExport;
use App\Http\Requests\CreditNotes\ExportCreditNoteRequest;
use Illuminate\Http\JsonResponse;

class ExportCreditNotesAction
{
    use ConfiguredTransactionExportAction;

    public function execute(ExportCreditNoteRequest $request): JsonResponse
    {
        return $this->exportWithConfiguration(
            'credit_notes',
            $request,
            CreditNoteExport::class
        );
    }
}
