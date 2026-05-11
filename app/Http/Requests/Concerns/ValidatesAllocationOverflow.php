<?php

namespace App\Http\Requests\Concerns;

use Closure;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesAllocationOverflow
{
    /**
     * @param  Closure(int): ?float  $getMaxAllocationFor
     */
    protected function validateAllocationOverflow(
        Validator $validator,
        string $inputKey,
        string $referenceIdKey,
        string $errorMessagePrefix,
        Closure $getMaxAllocationFor,
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

            if ((float) $allocation['allocated_amount'] > $maxAllocation) {
                $validator->errors()->add(
                    "{$inputKey}.{$index}.allocated_amount",
                    $errorMessagePrefix . ': ' . $maxAllocation,
                );
            }
        }
    }
}
