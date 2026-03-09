<?php

namespace App\Http\Resources\AssetMaintenances;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
class AssetMaintenanceCollection extends ResourceCollection
{
    public $collects = AssetMaintenanceResource::class;

    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}
