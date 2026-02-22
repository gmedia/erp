<?php

namespace App\Exports;

use App\Actions\Reports\IndexMaintenanceCostReportAction;
use App\Http\Requests\Reports\IndexMaintenanceCostRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MaintenanceCostExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->filters['export'] = true; // Flag for action to return all without pagination
    }

    public function collection()
    {
        $action = app(IndexMaintenanceCostReportAction::class);

        $request = new IndexMaintenanceCostRequest();
        $request->merge($this->filters);

        return $action->execute($request);
    }

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
            $maintenance->asset?->asset_code ?? '-',
            $maintenance->asset?->name ?? '-',
            $maintenance->asset?->category?->name ?? '-',
            $maintenance->asset?->branch?->name ?? '-',
            ucfirst($maintenance->maintenance_type),
            ucfirst($maintenance->status),
            $maintenance->scheduled_at?->format('Y-m-d H:i') ?? '-',
            $maintenance->performed_at?->format('Y-m-d H:i') ?? '-',
            $maintenance->supplier?->name ?? '-',
            $maintenance->cost,
            $maintenance->notes ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
