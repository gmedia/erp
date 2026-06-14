<?php

namespace App\Actions\Concerns;

use App\Services\Currency\CurrencyGuard;
use Illuminate\Database\Query\Builder;

trait AssertsSingleCurrency
{
    private ?CurrencyGuard $currencyGuard = null;

    protected function assertSingleCurrency(
        Builder $query,
        string $context,
        string $column = 'currency',
    ): void {
        $this->currencyGuard()->assertHomogeneousQuery($query, $context, $column);
    }

    private function currencyGuard(): CurrencyGuard
    {
        return $this->currencyGuard ??= app(CurrencyGuard::class);
    }
}
