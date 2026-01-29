<?php

namespace App\Http\Requests\Units;

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\Unit;

class StoreUnitRequest extends SimpleCrudStoreRequest
{
    public function getModelClass(): string
    {
        return Unit::class;
    }
}
