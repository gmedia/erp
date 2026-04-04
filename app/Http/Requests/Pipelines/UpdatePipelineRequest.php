<?php

namespace App\Http\Requests\Pipelines;

use Illuminate\Validation\Rule;

class UpdatePipelineRequest extends AbstractPipelineRequest
{
    public function rules(): array
    {
        return collect(parent::rules())
            ->map(fn (array $rules) => ['sometimes', ...$rules])
            ->all();
    }

    protected function pipelineCodeUniqueRule(): Rule|string
    {
        return Rule::unique('pipelines')->ignore($this->pipeline);
    }
}
