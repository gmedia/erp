<?php

namespace App\Http\Requests\AssetLocations;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractAssetLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => $this->withSometimes('required|exists:branches,id'),
            'parent_id' => $this->withSometimes('nullable|exists:asset_locations,id'),
            'code' => $this->withSometimes('required|string|max:50'),
            'name' => $this->withSometimes('required|string|max:255'),
        ];
    }

    protected function withSometimes(string $rules): string
    {
        if (! $this->usesSometimes()) {
            return $rules;
        }

        return 'sometimes|' . $rules;
    }

    abstract protected function usesSometimes(): bool;
}
