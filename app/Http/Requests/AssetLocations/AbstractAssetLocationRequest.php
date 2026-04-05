<?php

namespace App\Http\Requests\AssetLocations;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesStringRules;

abstract class AbstractAssetLocationRequest extends AuthorizedFormRequest
{
    use HasSometimesStringRules;

    public function rules(): array
    {
        return [
            'branch_id' => $this->withSometimes('required|exists:branches,id'),
            'parent_id' => $this->withSometimes('nullable|exists:asset_locations,id'),
            'code' => $this->withSometimes('required|string|max:50'),
            'name' => $this->withSometimes('required|string|max:255'),
        ];
    }

    abstract protected function usesSometimes(): bool;
}
