<?php

namespace App\Http\Resources\Concerns;

trait BuildsPartyResourceData
{
    /**
     * @return array<string, mixed>
     */
    protected function partyResourceData(bool $includeNotes = false): array
    {
        $data = [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'address' => $this->resource->address,
            'branch' => [
                'id' => $this->resource->branch_id,
                'name' => $this->resource->branch?->name,
            ],
            'category' => [
                'id' => $this->resource->category_id,
                'name' => $this->resource->category?->name,
            ],
            'status' => $this->resource->status,
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];

        if ($includeNotes) {
            $data['notes'] = $this->resource->notes;
        }

        return $data;
    }
}
