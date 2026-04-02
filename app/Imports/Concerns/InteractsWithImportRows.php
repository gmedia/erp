<?php

namespace App\Imports\Concerns;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Collection;
use Throwable;

trait InteractsWithImportRows
{
    /**
     * @param  callable(): mixed  $operation
     */
    protected function performImportUpsert(int $rowNumber, callable $operation, bool $incrementSkippedOnError = false): void
    {
        try {
            $operation();
            $this->importedCount++;
        } catch (Throwable $exception) {
            $this->recordSystemError($rowNumber, $exception);

            if ($incrementSkippedOnError) {
                $this->skippedCount++;
            }
        }
    }

    protected function recordValidationErrors(ValidatorContract $validator, int $rowNumber): void
    {
        foreach ($validator->errors()->all() as $error) {
            $this->errors[] = [
                'row' => $rowNumber,
                'field' => 'Validation',
                'message' => $error,
            ];
        }
    }

    protected function recordLookupError(int $rowNumber, string $field, string $entity, mixed $value): void
    {
        $this->errors[] = [
            'row' => $rowNumber,
            'field' => $field,
            'message' => $entity . " '" . $value . "' not found.",
        ];
    }

    protected function recordSystemError(int $rowNumber, Throwable $exception): void
    {
        $this->errors[] = [
            'row' => $rowNumber,
            'field' => 'System',
            'message' => 'Failed to save: ' . $exception->getMessage(),
        ];
    }

    protected function resolveLookupId(
        Collection $lookup,
        mixed $value,
        int $rowNumber,
        string $field,
        string $entity,
        bool $required = true
    ): mixed {
        if (empty($value)) {
            return $required ? $this->missingLookupValue($rowNumber, $field, $entity) : null;
        }

        $resolvedId = $lookup->get($value);
        if ($resolvedId !== null) {
            return $resolvedId;
        }

        $this->recordLookupError($rowNumber, $field, $entity, $value);

        return null;
    }

    protected function missingLookupValue(int $rowNumber, string $field, string $entity): null
    {
        $this->recordLookupError($rowNumber, $field, $entity, '');

        return null;
    }
}
