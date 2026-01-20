<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Menu
 */
class MenuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'display_name' => $this->display_name,
            'icon' => $this->icon,
            'url' => $this->url ? "/{$this->url}" : null,
            'children' => $this->relationLoaded('children')
                ? MenuResource::collection($this->children)->resolve()
                : [],
        ];
    }
}
