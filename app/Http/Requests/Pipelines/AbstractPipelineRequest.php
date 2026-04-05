<?php

namespace App\Http\Requests\Pipelines;

use App\Http\Requests\AuthorizedFormRequest;
use Illuminate\Validation\Rule;

abstract class AbstractPipelineRequest extends AuthorizedFormRequest
{
    public function rules(): array
    {
        return [
            'name' => $this->requiredStringRules(),
            'code' => [
                ...$this->requiredStringRules(),
                $this->pipelineCodeUniqueRule(),
            ],
            'entity_type' => $this->requiredStringRules(),
            'description' => $this->optionalStringRules(),
            'version' => $this->optionalIntegerRules(),
            'is_active' => $this->optionalBooleanRules(),
            'conditions' => $this->optionalJsonRules(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function requiredStringRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * @return array<int, string>
     */
    protected function optionalStringRules(): array
    {
        return ['nullable', 'string'];
    }

    /**
     * @return array<int, string>
     */
    protected function optionalIntegerRules(): array
    {
        return ['nullable', 'integer', 'min:1'];
    }

    /**
     * @return array<int, string>
     */
    protected function optionalBooleanRules(): array
    {
        return ['nullable', 'boolean'];
    }

    /**
     * @return array<int, string>
     */
    protected function optionalJsonRules(): array
    {
        return ['nullable', 'json'];
    }

    abstract protected function pipelineCodeUniqueRule(): Rule|string;
}
