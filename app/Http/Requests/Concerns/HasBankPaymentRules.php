<?php

namespace App\Http\Requests\Concerns;

trait HasBankPaymentRules
{
    use HasSupportedCurrencyRules;

    /**
     * Shared rules for bank-payment-style transactions (AP payment, AR receipt).
     *
     * @param  array<int, string>  $paymentMethods
     * @return array<string, array<int, mixed>>
     */
    protected function bankPaymentSharedRules(string $dateField, array $paymentMethods): array
    {
        return [
            'branch_id' => $this->withSometimes(['required', 'integer', 'exists:branches,id']),
            'fiscal_year_id' => $this->withSometimes(['required', 'integer', 'exists:fiscal_years,id']),
            $dateField => $this->withSometimes(['required', 'date']),
            'payment_method' => $this->withSometimes([
                'required',
                'string',
                'in:' . implode(',', $paymentMethods),
            ]),
            'bank_account_id' => $this->withSometimes(['required', 'integer', 'exists:accounts,id']),
            'currency' => $this->withSometimes(['required', ...$this->supportedCurrencyRules()]),
            'reference' => $this->withSometimes(['nullable', 'string', 'max:255']),
            'notes' => $this->withSometimes(['nullable', 'string']),
        ];
    }
}
