<?php

namespace App\Http\Requests\AssetMaintenances;

class StoreAssetMaintenanceRequest extends AbstractAssetMaintenanceMutationRequest
{
    protected function usesSometimes(): bool
    {
        return false;
    }
}
