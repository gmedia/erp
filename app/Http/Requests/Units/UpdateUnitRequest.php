<?php

namespace App\Http\Requests\Units;

use App\Http\Requests\SimpleCrudUpdateRequest;
use App\Models\Unit;

class UpdateUnitRequest extends SimpleCrudUpdateRequest
{
    public function getModelClass(): string
    {
        return Unit::class;
    }
}
