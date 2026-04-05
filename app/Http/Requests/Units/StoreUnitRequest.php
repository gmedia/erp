<?php

namespace App\Http\Requests\Units;

use App\Http\Requests\SimpleCrudStoreRequest;

class StoreUnitRequest extends SimpleCrudStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'symbol' => 'nullable|string|max:10',
        ]);
    }
}
