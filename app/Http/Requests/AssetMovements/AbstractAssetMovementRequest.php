<?php

namespace App\Http\Requests\AssetMovements;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractAssetMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
