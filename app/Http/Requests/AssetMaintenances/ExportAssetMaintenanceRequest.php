<?php

namespace App\Http\Requests\AssetMaintenances;

class ExportAssetMaintenanceRequest extends AbstractAssetMaintenanceListingRequest
{
    public function rules(): array
    {
        return $this->assetMaintenanceListingRules(
            'id,asset,maintenance_type,status,scheduled_at,performed_at,supplier,cost,created_at',
        );
    }
}
