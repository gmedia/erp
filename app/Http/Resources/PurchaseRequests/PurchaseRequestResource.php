<?php

namespace App\Http\Resources\PurchaseRequests;

use App\Http\Resources\Concerns\BuildsProductUnitItemResourceData;
use App\Models\Branch;
use App\Models\Department;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property PurchaseRequest $resource
 */
class PurchaseRequestResource extends JsonResource
{
    use BuildsProductUnitItemResourceData;

    public function toArray($request): array
    {
        /** @var Branch|null $branch */
        $branch = $this->resource->branch;
        /** @var Department|null $department */
        $department = $this->resource->department;
        /** @var User|null $requester */
        $requester = $this->resource->requester;
        /** @var User|null $approver */
        $approver = $this->resource->approver;
        /** @var User|null $creator */
        $creator = $this->resource->creator;

        return [
            'id' => $this->resource->id,
            'pr_number' => $this->resource->pr_number,
            'branch' => [
                'id' => $this->resource->branch_id,
                'name' => $branch?->name,
            ],
            'department' => [
                'id' => $this->resource->department_id,
                'name' => $department?->name,
            ],
            'requester' => [
                'id' => $this->resource->requested_by,
                'name' => $requester?->name,
            ],
            'request_date' => $this->resource->request_date->toDateString(),
            'required_date' => $this->resource->required_date?->toDateString(),
            'priority' => $this->resource->priority,
            'status' => $this->resource->status,
            'estimated_amount' => (string) $this->resource->estimated_amount,
            'notes' => $this->resource->notes,
            'rejection_reason' => $this->resource->rejection_reason,
            'approved_by' => $this->resource->approved_by ? [
                'id' => $this->resource->approved_by,
                'name' => $approver?->name,
            ] : null,
            'approved_at' => $this->resource->approved_at?->toIso8601String(),
            'created_by' => $this->resource->created_by ? [
                'id' => $this->resource->created_by,
                'name' => $creator?->name,
            ] : null,
            'items' => $this->resource->relationLoaded('items')
                ? $this->resource->items->map(fn (PurchaseRequestItem $item) => $this->productUnitItemResourceData($item, [
                    'quantity' => (string) $item->quantity,
                    'quantity_ordered' => (string) $item->quantity_ordered,
                    'estimated_unit_price' => (string) $item->estimated_unit_price,
                    'estimated_total' => (string) $item->estimated_total,
                    'notes' => $item->notes,
                ]))->values()
                : [],
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
