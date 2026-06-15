<?php

namespace App\Exceptions\Currency;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class MixedCurrencyException extends HttpResponseException
{
    /**
     * @param  list<string>  $currencies
     */
    public function __construct(
        public readonly string $context,
        public readonly array $currencies,
    ) {
        parent::__construct(new JsonResponse([
            'message' => sprintf(
                'Mixed currencies detected in %s: [%s]. Aggregation requires a single currency.',
                $context,
                implode(', ', $currencies),
            ),
            'errors' => [
                'currency' => [
                    sprintf(
                        'Found %d distinct currencies (%s) where 1 was expected.',
                        count($currencies),
                        implode(', ', $currencies),
                    ),
                ],
            ],
        ], 422));
    }
}
