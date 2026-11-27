<?php

namespace App\Http\Resources\VehicleLoans;

use App\Http\Resources\SimpleCrudResource;

class VehicleLoanResource extends SimpleCrudResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,
            'requester_id' => $this->resource->requester_id,
            'requester_name' => $this->resource->requester?->name,
            'department_id' => $this->resource->department_id,
            'department_name' => $this->resource->department?->name,
            'branch_id' => $this->resource->branch_id,
            'company_id' => $this->resource->company_id,
            'start_at' => $this->resource->start_at?->toIso8601String(),
            'end_at' => $this->resource->end_at?->toIso8601String(),
            'purpose' => $this->resource->purpose,
            'driver_id' => $this->resource->driver_id,
            'driver_name' => $this->resource->driver?->employee?->name,
            'vehicle_unit_id' => $this->resource->vehicle_unit_id,
            'plate_number' => $this->resource->vehicleUnit?->plate_number,
            'status' => $this->resource->status,
            'is_overdue' => (bool) $this->resource->is_overdue,
            'submitted_at' => $this->resource->submitted_at?->toIso8601String(),
            'approved_at' => $this->resource->approved_at?->toIso8601String(),
            'rejection_reason' => $this->resource->rejection_reason,
            'review_notes' => $this->resource->review_notes,
            'pickup' => $this->resource->pickup,
            'returns' => $this->resource->returns,
            'approval_requests' => $this->whenLoaded(
                'approvalRequests',
                fn () => $this->resource->approvalRequests->map(fn ($approval): array => [
                    'id' => $approval->id,
                    'status' => $approval->status,
                    'current_step_order' => $approval->current_step_order,
                    'submitted_at' => $approval->submitted_at?->toIso8601String(),
                    'completed_at' => $approval->completed_at?->toIso8601String(),
                    'steps' => $approval->steps->map(fn ($step): array => [
                        'step_order' => $step->step_order,
                        'step_name' => $step->flowStep?->name,
                        'status' => $step->status,
                        'action' => $step->action,
                        'comments' => $step->comments,
                        'acted_at' => $step->acted_at?->toIso8601String(),
                        'approver_name' => $step->flowStep?->user?->name,
                    ])->values(),
                ])->values(),
            ),
            'activity_logs' => $this->whenLoaded(
                'activityLogs',
                fn () => $this->resource->activityLogs->map(fn ($log): array => [
                    'id' => $log->id,
                    'event' => $log->event,
                    'actor_name' => $log->actor?->name,
                    'created_at' => $log->created_at?->toIso8601String(),
                ])->values(),
            ),
            'created_at' => $this->resource->created_at?->toIso8601String(),
        ];
    }
}
