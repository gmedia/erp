<?php

namespace App\Http\Resources\AssetMaintenances;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
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
