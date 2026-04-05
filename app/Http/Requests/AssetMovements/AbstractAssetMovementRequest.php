<?php

namespace App\Http\Requests\AssetMovements;

use App\Http\Requests\AuthorizedFormRequest;

abstract class AbstractAssetMovementRequest extends AuthorizedFormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function baseRules(): array
    {
        return [
            'moved_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
