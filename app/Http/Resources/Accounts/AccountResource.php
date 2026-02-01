<?php

namespace App\Http\Resources\Accounts;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'coa_version_id' => $this->coa_version_id,
            'parent_id' => $this->parent_id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'sub_type' => $this->sub_type,
            'normal_balance' => $this->normal_balance,
            'level' => $this->level,
            'is_active' => $this->is_active,
            'is_cash_flow' => $this->is_cash_flow,
            'description' => $this->description,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            // Recursive children for tree view if needed, but usually we flat load and build tree in frontend
            // or use specific action for tree.
        ];
    }
}
