<?php

namespace App\Http\Requests\Concerns;

trait HasSometimesArrayRules
{
    abstract protected function usesSometimes(): bool;

    /**
     * @param  array<int, string|object>  $rules
     * @return array<int, string|object>
     */
    protected function withSometimes(array $rules): array
    {
        if (! $this->usesSometimes()) {
            return $rules;
        }

        return ['sometimes', ...$rules];
    }

    /**
     * @return array<int, string>
     */
    protected function itemsRules(): array
    {
        if (! $this->usesSometimes()) {
            return ['required', 'array', 'min:1'];
        }

        return ['sometimes', 'array', 'min:1'];
    }

    protected function itemRequiredRule(): string
    {
        return $this->usesSometimes() ? 'required_with:items' : 'required';
    }

    /**
     * @return array<int, string>
     */
    protected function requiredIntegerItemRule(string $existsRule): array
    {
        return [$this->itemRequiredRule(), 'integer', $existsRule];
    }

    /**
     * @return array<int, string>
     */
    protected function nullableIntegerItemRule(string $existsRule): array
    {
        return ['nullable', 'integer', $existsRule];
    }

    /**
     * @return array<int, string>
     */
    protected function requiredNumericItemRule(string $constraintRule): array
    {
        return [$this->itemRequiredRule(), 'numeric', $constraintRule];
    }

    /**
     * @return array<int, string>
     */
    protected function nullableNumericItemRule(string $constraintRule): array
    {
        return ['nullable', 'numeric', $constraintRule];
    }

    /**
     * @return array<int, string>
     */
    protected function nullableStringItemRule(): array
    {
        return ['nullable', 'string'];
    }
}
