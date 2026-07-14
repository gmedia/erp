<?php

namespace App\Http\Resources\Employees;

use App\Http\Resources\Users\UserResource;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @mixin Employee
 *
 * @property-read Carbon|null $tenure
 */
class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     */
    public function toArray($request): array
    {
        $currentEmployment = $this->whenLoaded('currentEmployment');
        $employments = $this->whenLoaded('employments');

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'user_id' => $this->user_id,
            'tenure' => $this->tenure?->toDateString(),
            'current_employment' => $currentEmployment
                ? new EmploymentResource($this->currentEmployment)
                : null,
            'employments' => $employments
                ? EmploymentResource::collection($this->employments)
                : [],
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'permissions' => $this->whenLoaded('permissions'),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
