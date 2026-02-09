<?php

namespace App\Http\Requests\AssetMovements;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'moved_at' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
