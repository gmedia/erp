<?php

namespace App\Exports;

use App\Domain\AssetMaintenances\AssetMaintenanceFilterService;
use App\Models\AssetMaintenance;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetMaintenanceExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
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
            $this->filters['sort_direction'] ?? 'desc',
            ['id', 'asset', 'maintenance_type', 'status', 'scheduled_at', 'performed_at', 'supplier', 'notes', 'cost', 'created_at']
        );

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Asset Code',
            'Asset Name',
            'Maintenance Type',
            'Status',
            'Scheduled At',
            'Performed At',
            'Supplier',
            'Cost',
            'Notes',
            'Created By',
            'Created At',
        ];
    }

    public function map($maintenance): array
    {
        return [
            $maintenance->id,
            $maintenance->asset?->asset_code,
            $maintenance->asset?->name,
            $maintenance->maintenance_type,
            $maintenance->status,
            $maintenance->scheduled_at?->format('Y-m-d H:i:s'),
            $maintenance->performed_at?->format('Y-m-d H:i:s'),
            $maintenance->supplier?->name,
            $maintenance->cost,
            $maintenance->notes,
            $maintenance->createdBy?->name,
            $maintenance->created_at?->toIso8601String(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
