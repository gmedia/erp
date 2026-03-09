<?php

namespace App\Http\Requests\Positions;

use App\Http\Requests\SimpleCrudStoreRequest;
use App\Models\Position;

class StorePositionRequest extends SimpleCrudStoreRequest
{
    public function getModelClass(): string
    {
        return Position::class;
    }
}
