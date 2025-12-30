<?php

namespace App\Http\Resources;

use App\Models\Employee;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Employee $resource
 */
class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'department' => $this->resource->department,
            'position' => $this->resource->position,
            'salary' => (string) $this->resource->salary,
            'hire_date' => $this->resource->hire_date->toIso8601String(),
            'created_at' => $this->resource->created_at ? $this->resource->created_at->toIso8601String() : null,
            'updated_at' => $this->resource->updated_at ? $this->resource->updated_at->toIso8601String() : null,
        ];
    }
}
