<?php

namespace App\Http\Requests\Concerns;

trait HasSometimesStringRules
{
    abstract protected function usesSometimes(): bool;

    protected function withSometimes(string $rules): string
    {
        if (! $this->usesSometimes()) {
            return $rules;
        }

        return 'sometimes|' . $rules;
    }
}
