<?php

namespace App\Http\Requests\Concerns;

trait HasTransactionAmountRules
{
    /**
     * Get shared transaction amount validation rules.
     *
     * @param  array<int, string>  $extraFields
     * @return array<string, array<int, string>>
     */
    protected function transactionAmountRules(array $extraFields = []): array
    {
        $rules = [
            'subtotal' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tax_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'discount_amount' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'grand_total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];

        foreach ($extraFields as $field) {
            $rules[$field] = ['sometimes', 'nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }
}
