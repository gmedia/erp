<?php

namespace App\Exports;

use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\ReportConfiguration;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class ReportConfigurationExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(protected array $filters = []) {}

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

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($item): array
    {
        return $this->mapExportRow($item, $this->columns());
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
