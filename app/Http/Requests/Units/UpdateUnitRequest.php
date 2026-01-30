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

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'symbol' => 'nullable|string|max:10',
        ]);
    }
}
