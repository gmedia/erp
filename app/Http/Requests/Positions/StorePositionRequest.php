<?php

namespace App\Http\Requests\Positions;

use App\Models\Position;
use App\Http\Requests\SimpleCrudStoreRequest;

class StorePositionRequest extends SimpleCrudStoreRequest
{
    public function getModelClass(): string
    {
        return Position::class;
    }
}
