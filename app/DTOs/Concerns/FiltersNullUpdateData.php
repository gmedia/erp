<?php

namespace App\DTOs\Concerns;

trait FiltersNullUpdateData
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function filterNullUpdateData(array $data): array
    {
        return array_filter(
            $data,
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
