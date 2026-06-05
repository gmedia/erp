<?php

namespace App\Http\Resources\Budgets;

use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property Budget $resource */
class BudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'ulid' => $this->resource->ulid,
            'fiscal_year_id' => $this->resource->fiscal_year_id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'budget_type' => $this->resource->budget_type,
            'status' => $this->resource->status,
            'total_amount' => (float) $this->resource->total_amount,
            'approved_by' => $this->resource->approved_by,
            'approved_at' => $this->resource->approved_at?->toIso8601String(),
            'created_by' => $this->resource->created_by,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
            'fiscal_year' => $this->whenLoaded(
                'fiscalYear',
                fn () => [
                    'id' => $this->resource->fiscalYear->id,
                    'name' => $this->resource->fiscalYear->name,
                ],
            ),
            'creator' => $this->whenLoaded(
                'creator',
                fn () => [
                    'id' => $this->resource->creator->id,
                    'name' => $this->resource->creator->name,
                ],
            ),
            'approver' => $this->whenLoaded(
                'approver',
                fn () => $this->resource->approver ? [
                    'id' => $this->resource->approver->id,
                    'name' => $this->resource->approver->name,
                ] : null,
            ),
            'lines' => $this->whenLoaded(
                'lines',
                fn () => $this->resource->lines->map(fn ($line) => [
                    'id' => $line->id,
                    'account_id' => $line->account_id,
                    'account' => $line->relationLoaded('account') ? [
                        'id' => $line->account->id,
                        'code' => $line->account->code,
                        'name' => $line->account->name,
                    ] : null,
                    'period_start' => $line->period_start->format('Y-m-d'),
                    'period_end' => $line->period_end->format('Y-m-d'),
                    'allocated_amount' => (float) $line->allocated_amount,
                    'notes' => $line->notes,
                ])->values()->all(),
            ),
        ];
    }
}
