<?php

namespace App\Http\Resources\AssetDepreciationRuns;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetDepreciationRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fiscal_year_id' => $this->fiscal_year_id,
            'fiscal_year' => $this->whenLoaded('fiscalYear', function () {
                return ['id' => $this->fiscalYear->id, 'name' => $this->fiscalYear->name];
            }),
            'period_start' => $this->period_start?->format('Y-m-d'),
            'period_end' => $this->period_end?->format('Y-m-d'),
            'status' => $this->status,
            'journal_entry_id' => $this->journal_entry_id,
            'journal_entry' => $this->whenLoaded('journalEntry', function () {
                return ['id' => $this->journalEntry->id, 'entry_number' => $this->journalEntry->entry_number];
            }),
            'created_by' => $this->created_by,
            'created_by_user' => $this->whenLoaded('createdBy', function () {
                return ['id' => $this->createdBy->id, 'name' => $this->createdBy->name];
            }),
            'posted_by' => $this->posted_by,
            'posted_by_user' => $this->whenLoaded('postedBy', function () {
                return ['id' => $this->postedBy->id, 'name' => $this->postedBy->name];
            }),
            'posted_at' => $this->posted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'lines_count' => $this->whenCounted('lines'),
        ];
    }
}
