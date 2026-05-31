<?php

namespace App\Http\Resources\PeriodClosings;

use App\Models\PeriodClosing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property PeriodClosing $resource */
class PeriodClosingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'period_month' => $this->resource->period_month,
            'period_year' => $this->resource->period_year,
            'closing_type' => $this->resource->closing_type,
            'status' => $this->resource->status,
            'net_income' => (float) $this->resource->net_income,
            'notes' => $this->resource->notes,
            'fiscal_year' => $this->whenLoaded(
                'fiscalYear',
                fn () => [
                    'id' => $this->resource->fiscalYear->id,
                    'name' => $this->resource->fiscalYear->name,
                ],
            ),
            'closing_journal_entry_id' => $this->resource->closing_journal_entry_id,
            'retained_earnings_account_id' => $this->resource->retained_earnings_account_id,
            'closed_by' => $this->whenLoaded(
                'closedBy',
                fn () => [
                    'id' => $this->resource->closed_by,
                    'name' => $this->resource->closedBy?->name,
                ],
            ),
            'closed_at' => $this->resource->closed_at?->toIso8601String(),
            'reopened_by' => $this->whenLoaded(
                'reopenedBy',
                fn () => [
                    'id' => $this->resource->reopened_by,
                    'name' => $this->resource->reopenedBy?->name,
                ],
            ),
            'reopened_at' => $this->resource->reopened_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
