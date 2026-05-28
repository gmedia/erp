<?php

namespace App\Http\Resources\AccountMappings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @mixin mixed|Model
 */
class AccountMappingCollection extends ResourceCollection
{
    public $collects = AccountMappingResource::class;
}
