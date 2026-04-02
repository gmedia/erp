<?php

namespace App\Imports\Concerns;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Throwable;

trait InteractsWithImportRows
{
    /**
     * @param  array<int, array{lookup: Collection, source: string, entity: string, target?: string, required?: bool, incrementSkippedOnFailure?: bool}>  $configs
     * @return array<string, mixed>|null
     */
    protected function resolveLookupAssignments(array $rowData, int $rowNumber, array $configs): ?array
    {
        $resolvedValues = [];

        foreach ($configs as $config) {
            $source = $config['source'];
            $required = $config['required'] ?? true;
            $value = $rowData[$source] ?? null;
            $resolvedId = $this->resolveLookupId(
                $config['lookup'],
                $value,
                $rowNumber,
                $source,
                $config['entity'],
                $required
            );

            $lookupFailed = $required
                ? $resolvedId === null
                : ! empty($value) && $resolvedId === null;

            if ($lookupFailed) {
                if ($config['incrementSkippedOnFailure'] ?? false) {
                    $this->skippedCount++;
                }

                return null;
            }

            $resolvedValues[$config['target'] ?? $source] = $resolvedId;
        }

        return $resolvedValues;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rowToArray(mixed $row): array
    {
        return is_array($row) ? $row : $row->toArray();
    }

    /**
     * @param  array<string, string|array<int, string>>  $rules
     */
    protected function validateImportRow(
        mixed $row,
        int $rowNumber,
        array $rules,
        bool $incrementSkippedOnFailure = false
    ): bool {
        $validator = Validator::make($this->rowToArray($row), $rules);

        if (! $validator->fails()) {
            return true;
        }

        $this->recordValidationErrors($validator, $rowNumber);

        if ($incrementSkippedOnFailure) {
            $this->skippedCount++;
        }

        return false;
    }

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
