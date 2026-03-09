<?php

namespace App\Http\Resources\AccountMappings;

use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|\Illuminate\Database\Eloquent\Model
 */
class AccountMappingCollection extends ResourceCollection
{
    public $collects = AccountMappingResource::class;
}
