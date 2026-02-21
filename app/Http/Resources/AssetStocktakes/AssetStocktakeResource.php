<?php

namespace App\Http\Resources\AssetStocktakes;

use App\Models\AssetStocktake;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AssetStocktake */
class AssetStocktakeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ulid' => $this->ulid,
            'reference' => $this->reference,
            'branch' => [
                'id' => $this->branch_id,
                'name' => $this->branch?->name,
            ],
            'planned_at' => $this->planned_at?->toIso8601String(),
            'performed_at' => $this->performed_at?->toIso8601String(),
            'status' => $this->status,
            'created_by' => [
                'id' => $this->created_by,
                'name' => $this->createdBy?->name,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
