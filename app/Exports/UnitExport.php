<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\Unit;

class UnitExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return Unit::class;
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Symbol', 'Created At', 'Updated At'];
    }

    public function map($item): array
    {
        return [
            $item->id,
            $item->name,
            $item->symbol,
            $item->created_at?->toIso8601String(),
            $item->updated_at?->toIso8601String(),
        ];
    }
}
