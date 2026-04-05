<?php

namespace App\Http\Requests\Pipelines;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractPipelineListingRequest extends BaseListingRequest
{
    protected function pipelineListingRules(string $sortBy): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'entity_type' => ['nullable', 'string'],
                'is_active' => ['nullable', 'boolean'],
            ],
            $this->listingSortRules($sortBy),
        );
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            if ($this->is_active === '' || $this->is_active === null) {
                $this->merge(['is_active' => null]);
            } else {
                $this->merge([
                    'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                ]);
            }
        }
    }
}
