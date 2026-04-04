<?php

namespace App\Actions\Products;

use App\Actions\Concerns\ConfiguredTimestampExportAction;
use App\Exports\ProductExport;

class ExportProductsAction extends ConfiguredTimestampExportAction
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function buildFilters(array $validated): array
    {
        $filters = [
            'search' => $validated['search'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'unit_id' => $validated['unit_id'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'type' => $validated['type'] ?? null,
            'status' => $validated['status'] ?? null,
            'billing_model' => $validated['billing_model'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        return array_filter($filters, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [];
    }

    protected function filenamePrefix(): string
    {
        return 'products';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new ProductExport($filters);
    }
}
