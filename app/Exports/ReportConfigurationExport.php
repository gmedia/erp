<?php

namespace App\Exports;

use App\Exports\Concerns\BaseExport;
use App\Models\ReportConfiguration;
use Illuminate\Database\Eloquent\Builder;

class ReportConfigurationExport extends BaseExport
{
    public function query(): Builder
    {
        $query = ReportConfiguration::query()->with(['creator', 'sections']);

        $this->applyConfiguredFilters(
            $query,
            $this->filters,
            ['code', 'name', 'description'],
            [
                'report_type' => 'report_type',
                'is_active' => 'is_active',
            ],
        );

        return $query->orderBy('name');
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (ReportConfiguration $item): mixed => $item->id,
            'Code' => fn (ReportConfiguration $item): mixed => $item->code,
            'Name' => fn (ReportConfiguration $item): mixed => $item->name,
            'Report Type' => fn (ReportConfiguration $item): mixed => $item->report_type,
            'Description' => fn (ReportConfiguration $item): mixed => $item->description,
            'Sections' => fn (ReportConfiguration $item): mixed => $item->sections->count(),
            'Active' => fn (ReportConfiguration $item): mixed => $item->is_active ? 'Yes' : 'No',
            'Created By' => fn (ReportConfiguration $item): mixed => $this->relatedAttribute($item, 'creator', 'name'),
            'Created At' => fn (ReportConfiguration $item): mixed => $this->formatIso8601($item->created_at),
        ];
    }
}
