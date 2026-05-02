<?php

namespace App\Http\Requests\Products;

use App\Http\Requests\AuthorizedFormRequest;
use App\Http\Requests\Concerns\HasSometimesArrayRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

abstract class AbstractProductRequest extends AuthorizedFormRequest
{
    use HasSometimesArrayRules;

    public function rules(): array
    {
        return [
            'code' => $this->withSometimes(['required', 'string', 'max:255', $this->codeUniqueRule()]),
            'name' => $this->withSometimes(['required', 'string', 'max:255']),
            'description' => $this->withSometimes(['nullable', 'string']),
            'type' => $this->withSometimes(['required', 'in:raw_material,work_in_progress,finished_good,purchased_good,service']),
            'product_category_id' => $this->withSometimes(['required', 'exists:product_categories,id']),
            'unit_id' => $this->withSometimes(['required', 'exists:units,id']),
            'branch_id' => $this->withSometimes(['nullable', 'exists:branches,id']),
            'cost' => $this->withSometimes(['required', 'numeric', 'min:0']),
            'selling_price' => $this->withSometimes(['required', 'numeric', 'min:0']),
            'billing_model' => $this->withSometimes(['required', 'in:one_time,subscription,both']),
            'status' => $this->withSometimes(['required', 'in:active,inactive,discontinued']),
            'notes' => $this->withSometimes(['nullable', 'string']),
        ];
    }

    abstract protected function usesSometimes(): bool;

    private function codeUniqueRule(): string|Unique
    {
        if (! $this->usesSometimes()) {
            return 'unique:products,code';
        }

        return Rule::unique('products', 'code')->ignore($this->route('product'));
    }
}
