<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\AccountMapping;

class AccountMappingExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return AccountMapping::class;
    }

    protected function getSortableFields(): array
    {
        return ['id', 'type', 'created_at', 'updated_at'];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Type',
            'Source COA Version',
            'Source Account Code',
            'Source Account Name',
            'Target COA Version',
            'Target Account Code',
            'Target Account Name',
            'Notes',
            'Created At',
            'Updated At',
        ];
    }

    public function map($model): array
    {
        $source = $model->sourceAccount;
        $target = $model->targetAccount;

        return [
            $model->id,
            $model->type,
            $source?->coaVersion?->name,
            $source?->code,
            $source?->name,
            $target?->coaVersion?->name,
            $target?->code,
            $target?->name,
            $model->notes,
            $model->created_at?->toIso8601String(),
            $model->updated_at?->toIso8601String(),
        ];
    }
}
