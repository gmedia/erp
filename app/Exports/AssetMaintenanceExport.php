<?php

namespace App\Exports;

use App\Domain\AssetMaintenances\AssetMaintenanceFilterService;
use App\Exports\Concerns\InteractsWithExportFilters;
use App\Models\AssetMaintenance;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;

class AssetMaintenanceExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    use InteractsWithExportFilters;

    public function __construct(
        protected array $filters = []
    ) {}

    public function query(): Builder
    {
        $query = AssetMaintenance::query()->with(['asset', 'supplier', 'createdBy']);

        $filterService = app(AssetMaintenanceFilterService::class);

        if (! empty($this->filters['search'])) {
            $filterService->applySearch($query, $this->filters['search'], ['notes', 'asset_name', 'asset_code']);
        } else {
            $filterService->applyAdvancedFilters($query, $this->filters);
        }

        $filterService->applyAdvancedFilters($query, [
            'scheduled_from' => $this->filters['scheduled_from'] ?? null,
            'scheduled_to' => $this->filters['scheduled_to'] ?? null,
            'performed_from' => $this->filters['performed_from'] ?? null,
            'performed_to' => $this->filters['performed_to'] ?? null,
            'cost_min' => $this->filters['cost_min'] ?? null,
            'cost_max' => $this->filters['cost_max'] ?? null,
        ]);

        $filterService->applySorting(
            $query,
            $this->filters['sort_by'] ?? 'scheduled_at',
            $this->normalizeSortDirection($this->filters),
            [
                'id',
                'asset',
                'maintenance_type',
                'status',
                'scheduled_at',
                'performed_at',
                'supplier',
                'notes',
                'cost',
                'created_at',
            ]
        );

        return $query;
    }

    public function headings(): array
    {
        return $this->exportHeadings($this->columns());
    }

    public function map($maintenance): array
    {
        return $this->mapExportRow($maintenance, $this->columns());
    }

    /**
     * @return array<string, callable(mixed): mixed>
     */
    protected function columns(): array
    {
        return [
            'ID' => fn (AssetMaintenance $m): mixed => $m->id,
            'Asset Code' => fn (AssetMaintenance $m): mixed => $m->asset->asset_code,
            'Asset Name' => fn (AssetMaintenance $m): mixed => $m->asset->name,
            'Maintenance Type' => fn (AssetMaintenance $m): mixed => $m->maintenance_type,
            'Status' => fn (AssetMaintenance $m): mixed => $m->status,
            'Scheduled At' => fn (AssetMaintenance $m): mixed => $this->formatDateValue($m->scheduled_at, 'Y-m-d H:i:s'),
            'Performed At' => fn (AssetMaintenance $m): mixed => $this->formatDateValue($m->performed_at, 'Y-m-d H:i:s'),
            'Supplier' => fn (AssetMaintenance $m): mixed => $m->supplier?->name,
            'Cost' => fn (AssetMaintenance $m): mixed => $m->cost,
            'Notes' => fn (AssetMaintenance $m): mixed => $m->notes,
            'Created By' => fn (AssetMaintenance $m): mixed => $this->relatedAttribute($m, 'createdBy', 'name'),
            'Created At' => fn (AssetMaintenance $m): mixed => $this->formatIso8601($m->created_at),
        ];
    }
}
