<?php

namespace App\Http\Resources\AssetDepreciationLines;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetDepreciationLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period' => $this->run?->period_start . ' - ' . $this->run?->period_end,
            'fiscal_year' => $this->run?->fiscalYear?->year,
            'amount' => (string) $this->amount,
            'accumulated_before' => (string) $this->accumulated_before,
            'accumulated_after' => (string) $this->accumulated_after,
            'book_value_after' => (string) $this->book_value_after,
            'status' => $this->run?->status,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
