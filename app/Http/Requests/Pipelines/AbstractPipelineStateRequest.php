<?php

namespace App\Http\Requests\Pipelines;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractPipelineStateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:255',
                $this->codeUniqueRule(),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:initial,intermediate,final'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['integer'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    abstract protected function codeUniqueRule(): object;
}
