<?php

namespace App\Http\Requests\AssetModels;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesStringRules;

abstract class AbstractAssetModelRequest extends AuthorizedFormRequest
{
    use HasSometimesStringRules;

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
