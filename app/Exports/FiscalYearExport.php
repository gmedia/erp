<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\FiscalYear;

/**
 * Export class for fiscal years.
 */
class FiscalYearExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return FiscalYear::class;
    }

    protected function getSortableFields(): array
    {
        return ['id', 'name', 'start_date', 'end_date', 'status', 'created_at', 'updated_at'];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Start Date',
            'End Date',
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
            $model->start_date?->toDateString(),
            $model->end_date?->toDateString(),
            $model->status,
            $model->created_at?->toIso8601String(),
            $model->updated_at?->toIso8601String(),
        ];
    }
}
