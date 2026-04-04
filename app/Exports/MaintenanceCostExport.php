<?php

namespace App\Exports;

use App\Actions\Reports\IndexMaintenanceCostReportAction;
use App\Exports\Concerns\AbstractActionCollectionExport;
use App\Http\Requests\Reports\IndexMaintenanceCostRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MaintenanceCostExport extends AbstractActionCollectionExport implements WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'Asset Code',
            'Asset Name',
            'Category',
            'Branch',
            'Maintenance Type',
            'Status',
            'Scheduled At',
            'Performed At',
            'Vendor/Supplier',
            'Cost',
            'Notes',
        ];
    }

    public function map($maintenance): array
    {
        return [
            $maintenance->asset->asset_code ?? '-',
            $maintenance->asset->name ?? '-',
            $maintenance->asset->category->name ?? '-',
            $maintenance->asset->branch->name ?? '-',
            ucfirst($maintenance->maintenance_type),
            ucfirst($maintenance->status),
            $maintenance->scheduled_at?->format('Y-m-d H:i') ?? '-',
            $maintenance->performed_at?->format('Y-m-d H:i') ?? '-',
            $maintenance->supplier->name ?? '-',
            $maintenance->cost,
            $maintenance->notes ?? '-',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function prepareFilters(array $filters): array
    {
        $filters['export'] = true;

        return $filters;
    }

    protected function actionClass(): string
    {
        return IndexMaintenanceCostReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexMaintenanceCostRequest::class;
    }
}
