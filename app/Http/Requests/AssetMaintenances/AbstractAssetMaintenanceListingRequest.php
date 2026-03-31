<?php

namespace App\Http\Requests\AssetMaintenances;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractAssetMaintenanceListingRequest extends BaseListingRequest
{
    protected function assetMaintenanceListingRules(string $sortBy): array
    {
        return [
            'search' => ['nullable', 'string'],
            'asset_id' => ['nullable', 'exists:assets,id'],
            'maintenance_type' => ['nullable', 'string', 'in:preventive,corrective,calibration,other'],
            'status' => ['nullable', 'string', 'in:scheduled,in_progress,completed,cancelled'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'created_by' => ['nullable', 'exists:users,id'],
            'scheduled_from' => ['nullable', 'date'],
            'scheduled_to' => ['nullable', 'date'],
            'performed_from' => ['nullable', 'date'],
            'performed_to' => ['nullable', 'date'],
            'cost_min' => ['nullable', 'numeric', 'min:0'],
            'cost_max' => ['nullable', 'numeric', 'min:0'],
            ...$this->listingSortRules($sortBy),
        ];
    }
}
