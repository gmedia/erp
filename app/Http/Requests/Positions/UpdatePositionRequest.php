<?php

namespace App\Http\Requests\Positions;

use App\Http\Requests\SimpleCrudUpdateRequest;
use App\Models\Position;

class UpdatePositionRequest extends SimpleCrudUpdateRequest
{
    public function getModelClass(): string
    {
        return Position::class;
    }
}
