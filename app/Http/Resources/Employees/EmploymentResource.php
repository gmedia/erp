<?php

namespace App\Http\Resources\Employees;

use App\Http\Resources\Branches\BranchResource;
use App\Http\Resources\Departments\DepartmentResource;
use App\Http\Resources\Positions\PositionResource;
use App\Models\Employment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Employment
 */
class EmploymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'company_id' => $this->company_id,
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'branch_id' => $this->branch_id,
            'salary' => $this->salary,
            'hire_date' => $this->hire_date->toDateString(),
            'termination_date' => $this->termination_date?->toDateString(),
            'employment_status' => $this->employment_status,
            'is_current' => $this->is_current,
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'position' => new PositionResource($this->whenLoaded('position')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'company' => $this->when($this->relationLoaded('company'), fn () => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
