<?php

namespace Tests\Unit\Requests\Concerns;

use App\Http\Requests\Concerns\ValidatesAllocationOverflow;
use Closure;
use Illuminate\Contracts\Validation\Validator;

/**
 * Harness exposing protected validateAllocationOverflow for unit testing.
 */
class ValidatesAllocationOverflowHarness
{
    use ValidatesAllocationOverflow;

    /**
     * @param  array<string, mixed>  $input
     */
    public function __construct(private array $input)
    {
        // Intentionally empty. Input is injected for test control.
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->input[$key] ?? $default;
    }

    public function runValidateAllocationOverflow(
        Validator $validator,
        string $inputKey,
        string $referenceIdKey,
        string $errorMessagePrefix,
        Closure $getMaxAllocationFor,
    ): void {
        $this->validateAllocationOverflow(
            $validator,
            $inputKey,
            $referenceIdKey,
            $errorMessagePrefix,
            $getMaxAllocationFor,
        );
    }
}
