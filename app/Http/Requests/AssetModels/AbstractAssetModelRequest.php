<?php

namespace App\Http\Requests\AssetModels;

use App\Http\Requests\Concerns\HasSometimesStringRules;
use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractAssetModelRequest extends FormRequest
{
    use HasSometimesStringRules;

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

    abstract protected function usesSometimes(): bool;
}
