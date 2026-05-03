<?php

namespace App\Http\Requests\Concerns;

trait HasTransactionAmountRules
{
    protected function getTransactionItemPricingRules(bool $required = true): array
    {
        $requiredRule = $required ? 'required' : 'nullable';

        return [
            'items.*.quantity' => [$requiredRule, 'numeric', 'gt:0'],
            'items.*.unit_price' => [$requiredRule, 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
