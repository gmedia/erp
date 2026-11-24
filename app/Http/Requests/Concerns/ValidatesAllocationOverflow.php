<?php

namespace App\Http\Requests\Concerns;

use Closure;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesAllocationOverflow
{
    /**
     * @param  Closure(int): ?float  $getMaxAllocationFor
     * @param  (Closure(array<string, mixed>): float)|null  $getConsumedAmount
     *                                                                          Nilai yang memakan sisa (default: allocated_amount)
     */
    protected function validateAllocationOverflow(
        Validator $validator,
        string $inputKey,
        string $referenceIdKey,
        string $errorMessagePrefix,
        Closure $getMaxAllocationFor,
        ?Closure $getConsumedAmount = null,
    ): void {
        $allocations = $this->input($inputKey, []);

        foreach ($allocations as $index => $allocation) {
            if (empty($allocation[$referenceIdKey]) || empty($allocation['allocated_amount'])) {
                continue;
            }

            $maxAllocation = $getMaxAllocationFor((int) $allocation[$referenceIdKey]);

            if ($maxAllocation === null) {
                continue;
            }

            $consumed = $getConsumedAmount !== null
                ? $getConsumedAmount($allocation)
                : (float) $allocation['allocated_amount'];

            if ($consumed > $maxAllocation) {
                $validator->errors()->add(
                    "{$inputKey}.{$index}.allocated_amount",
                    $errorMessagePrefix . ': ' . $maxAllocation,
                );
            }
        }
    }
}
