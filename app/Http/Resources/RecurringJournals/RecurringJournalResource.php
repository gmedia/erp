<?php

namespace App\Http\Resources\RecurringJournals;

use App\Models\RecurringJournal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property RecurringJournal $resource */
class RecurringJournalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'frequency' => $this->resource->frequency,
            'next_run_date' => $this->resource->next_run_date->format('Y-m-d'),
            'last_run_date' => $this->resource->last_run_date?->format('Y-m-d'),
            'end_date' => $this->resource->end_date?->format('Y-m-d'),
            'total_amount' => (float) $this->resource->total_amount,
            'auto_post' => $this->resource->auto_post,
            'is_active' => $this->resource->is_active,
            'fiscal_year' => $this->whenLoaded(
                'fiscalYear',
                fn () => [
                    'id' => $this->resource->fiscalYear?->id,
                    'name' => $this->resource->fiscalYear?->name,
                ],
            ),
            'lines' => $this->whenLoaded(
                'lines',
                fn () => $this->resource->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'account_id' => $line->account_id,
                    'account_code' => $line->account->code,
                    'account_name' => $line->account->name,
                    'debit' => (float) $line->debit,
                    'credit' => (float) $line->credit,
                    'memo' => $line->memo,
                ])->values()->all(),
            ),
            'created_by' => $this->whenLoaded(
                'creator',
                fn () => [
                    'id' => $this->resource->created_by,
                    'name' => $this->resource->creator?->name,
                ],
            ),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
