<?php

namespace App\Http\Requests\AssetModels;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractAssetModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_category_id' => $this->withSometimes('required|exists:asset_categories,id'),
            'manufacturer' => $this->withSometimes('nullable|string|max:255'),
            'model_name' => $this->withSometimes('required|string|max:255'),
            'specs' => $this->withSometimes('nullable|array'),
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
