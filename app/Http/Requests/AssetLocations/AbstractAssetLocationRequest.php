<?php

namespace App\Http\Requests\AssetLocations;

use App\Http\Requests\Concerns\HasSometimesStringRules;
use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractAssetLocationRequest extends FormRequest
{
    use HasSometimesStringRules;

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

    abstract protected function usesSometimes(): bool;
}
