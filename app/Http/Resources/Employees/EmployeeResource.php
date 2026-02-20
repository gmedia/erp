<?php

namespace App\Http\Resources\Employees;

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
            'employee_id' => $this->resource->employee_id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'department' => [
                'id' => $this->resource->department_id,
                'name' => $this->resource->department?->name,
            ],
            'position' => [
                'id' => $this->resource->position_id,
                'name' => $this->resource->position?->name,
            ],
            'branch' => [
                'id' => $this->resource->branch_id,
                'name' => $this->resource->branch?->name,
            ],
            'employment_status' => $this->resource->employment_status,
            'salary' => $this->resource->salary ? (string) $this->resource->salary : null,
            'hire_date' => $this->resource->hire_date?->toIso8601String(),
            'termination_date' => $this->resource->termination_date?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
