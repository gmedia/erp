<?php

namespace App\Http\Requests\Units;

use App\Http\Requests\SimpleCrudUpdateRequest;

class UpdateUnitRequest extends SimpleCrudUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'symbol' => 'nullable|string|max:10',
        ]);
    }
}
