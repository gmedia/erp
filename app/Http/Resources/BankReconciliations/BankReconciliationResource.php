<?php

namespace App\Http\Resources\BankReconciliations;

use App\Models\BankReconciliation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property BankReconciliation $resource */
class BankReconciliationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'reconciliation_date' => $this->resource->reconciliation_date->format('Y-m-d'),
            'period_start' => $this->resource->period_start->format('Y-m-d'),
            'period_end' => $this->resource->period_end->format('Y-m-d'),
            'statement_balance' => (float) $this->resource->statement_balance,
            'book_balance' => (float) $this->resource->book_balance,
            'reconciled_balance' => (float) $this->resource->reconciled_balance,
            'difference' => (float) $this->resource->difference,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'account' => $this->whenLoaded(
                'account',
                fn () => [
                    'id' => $this->resource->account->id,
                    'code' => $this->resource->account->code,
                    'name' => $this->resource->account->name,
                ],
            ),
            'fiscal_year' => $this->whenLoaded(
                'fiscalYear',
                fn () => [
                    'id' => $this->resource->fiscalYear->id,
                    'name' => $this->resource->fiscalYear->name,
                ],
            ),
            'items' => $this->whenLoaded(
                'items',
                fn () => $this->resource->items->map(fn ($item) => [
                    'id' => $item->id,
                    'journal_entry_line_id' => $item->journal_entry_line_id,
                    'transaction_date' => $item->transaction_date->format('Y-m-d'),
                    'description' => $item->description,
                    'debit' => (float) $item->debit,
                    'credit' => (float) $item->credit,
                    'type' => $item->type,
                    'is_reconciled' => (bool) $item->is_reconciled,
                    'reference' => $item->reference,
                    'notes' => $item->notes,
                    'account_id' => $item->account_id,
                    'account' => $item->account_id ? [
                        'id' => $item->account->id,
                        'code' => $item->account->code,
                        'name' => $item->account->name,
                    ] : null,
                    'journal_entry_number' => $item->journalEntryLine?->journalEntry?->entry_number,
                ])->values()->all(),
            ),
            'completed_by' => $this->whenLoaded(
                'completedBy',
                fn () => [
                    'id' => $this->resource->completed_by,
                    'name' => $this->resource->completedBy?->name,
                ],
            ),
            'completed_at' => $this->resource->completed_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
