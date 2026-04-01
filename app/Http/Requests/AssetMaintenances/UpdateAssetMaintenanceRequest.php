<?php

namespace App\Http\Requests\AssetMaintenances;

class UpdateAssetMaintenanceRequest extends AbstractAssetMaintenanceMutationRequest
{
    protected function usesSometimes(): bool
    {
        return true;
    }
}
