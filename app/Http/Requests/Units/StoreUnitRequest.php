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

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'symbol' => 'nullable|string|max:10',
        ]);
    }
}
