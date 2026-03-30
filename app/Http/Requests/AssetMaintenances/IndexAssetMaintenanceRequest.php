<?php

namespace App\Http\Requests\AssetMaintenances;

class IndexAssetMaintenanceRequest extends AbstractAssetMaintenanceListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->assetMaintenanceListingRules(
                'id,asset,maintenance_type,status,scheduled_at,performed_at,supplier,notes,cost,created_at,updated_at',
            ),
            [
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1'],
            ],
        );
    }
}
