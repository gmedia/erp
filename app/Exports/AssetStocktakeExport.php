<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\AssetStocktake;
use Illuminate\Database\Eloquent\Builder;

class AssetStocktakeExport extends BaseExport
{
    public function query(): Builder
    {
        $query = AssetStocktake::query()->with(['branch', 'createdBy']);

        $this->applySearchFilter($query, $this->filters, ['reference']);
        $this->applyExactFilters($query, $this->filters, [
            'branch' => 'branch_id',
            'status' => 'status',
        ]);
        $this->applySorting($query, $this->filters, [
            'reference',
            'branch_id',
            'planned_at',
            'performed_at',
            'status',
            'created_at',
        ]);

        return $query;
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (AssetStocktake $s): mixed => $s->id,
            'Reference' => fn (AssetStocktake $s): mixed => $s->reference,
            'Branch' => fn (AssetStocktake $s): mixed => $this->relatedAttribute($s, 'branch', 'name'),
            'Planned At' => fn (AssetStocktake $s): mixed => $this->formatDateValue($s->planned_at, 'Y-m-d H:i'),
            'Performed At' => fn (AssetStocktake $s): mixed => $this->formatDateValue($s->performed_at, 'Y-m-d H:i'),
            'Status' => fn (AssetStocktake $s): mixed => $s->status,
            'Created By' => fn (AssetStocktake $s): mixed => $this->relatedAttribute($s, 'createdBy', 'name'),
            'Created At' => fn (AssetStocktake $s): mixed => $this->formatIso8601($s->created_at),
        ];
    }
}
