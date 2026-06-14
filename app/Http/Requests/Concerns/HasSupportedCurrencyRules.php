<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait HasSupportedCurrencyRules
{
    /**
     * Get validation rules for a transactional `currency` field.
     *
     * @return array<int, string|object>
     */
    protected function supportedCurrencyRules(): array
    {
        /** @var array<int, string> $supported */
        $supported = config('app.supported_transaction_currencies', ['IDR']);

        return [
            'string',
            'size:3',
            Rule::in($supported),
        ];
    }
}
