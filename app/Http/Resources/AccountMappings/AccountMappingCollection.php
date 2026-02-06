<?php

namespace App\Http\Resources\AccountMappings;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AccountMappingCollection extends ResourceCollection
{
    public $collects = AccountMappingResource::class;
}
