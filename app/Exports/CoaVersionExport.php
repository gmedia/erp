<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\CoaVersion;

/**
 * Export class for COA versions.
 */
class CoaVersionExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return CoaVersion::class;
    }

    protected function getSortableFields(): array
    {
        return ['id', 'name', 'fiscal_year_id', 'status', 'created_at', 'updated_at'];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Fiscal Year',
            'Status',
            'Created At',
            'Updated At',
        ];
    }

    public function map($model): array
    {
        return [
            $model->id,
            $model->name,
            $model->fiscalYear?->name,
            $model->status,
            $model->created_at?->toIso8601String(),
            $model->updated_at?->toIso8601String(),
        ];
    }
}
