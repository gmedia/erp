<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\ProductCategory;

class ProductCategoryExport extends SimpleCrudExport
{
    /**
     * Define the headings for the Excel sheet.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Description',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * Map each model to a row.
     */
    public function map($model): array
    {
        return [
            $model->id,
            $model->name,
            $model->description,
            $model->created_at ? $model->created_at->toIso8601String() : null,
            $model->updated_at ? $model->updated_at->toIso8601String() : null,
        ];
    }

    protected function getModelClass(): string
    {
        return ProductCategory::class;
    }
}
