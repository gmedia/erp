<?php

namespace App\Http\Resources\JournalEntries;

use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property JournalEntry $resource */
class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'entry_number' => $this->resource->entry_number,
            'entry_date' => $this->resource->entry_date->format('Y-m-d'),
            'reference' => $this->resource->reference,
            'description' => $this->resource->description,
            'status' => $this->resource->status,
            'fiscal_year' => [
                'id' => $this->resource->fiscal_year_id,
                'name' => $this->resource->fiscalYear?->name,
            ],
            'lines' => $this->resource->lines->map(fn($line) => [
                'id' => $line->id,
                'account_id' => $line->account_id,
                'account_name' => $line->account?->name,
                'account_code' => $line->account?->code,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'memo' => $line->memo,
            ]),
            'total_debit' => $this->resource->total_debit,
            'total_credit' => $this->resource->total_credit,
            'created_by' => [
                'id' => $this->resource->created_by,
                'name' => $this->resource->createdBy?->name,
            ],
            'created_at' => $this->resource->created_at->toIso8601String(),
            'updated_at' => $this->resource->updated_at->toIso8601String(),
        ];
    }
}
