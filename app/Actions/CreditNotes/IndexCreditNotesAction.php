<?php

namespace App\Actions\CreditNotes;

use App\Actions\Concerns\ExecutesConfiguredMappedIndexRequest;
use App\Actions\Concerns\TransactionMappedIndexConfigurations;
use App\Domain\CreditNotes\CreditNoteFilterService;
use App\Http\Requests\CreditNotes\IndexCreditNoteRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexCreditNotesAction
{
    use ExecutesConfiguredMappedIndexRequest;

    public function __construct(
        private CreditNoteFilterService $filterService
    ) {}

    public function execute(IndexCreditNoteRequest $request): LengthAwarePaginator
    {
        return $this->executeConfiguredMappedIndexRequest(
            $request,
            $this->filterService,
            TransactionMappedIndexConfigurations::for('credit_notes'),
        );
    }
}
