<?php

namespace App\Exports;

use App\Actions\Reports\IndexInventoryValuationReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Http\Requests\Reports\IndexInventoryValuationReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryValuationReportExport extends AbstractReportIndexExport implements WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'Product Code',
            'Product Name',
            'Category',
            'Unit',
            'Warehouse Code',
            'Warehouse Name',
            'Branch',
            'Quantity On Hand',
            'Average Cost',
            'Stock Value',
            'Last Movement',
        ];
    }

    public function map($stock): array
    {
        return [
            $stock->product->code ?? '-',
            $stock->product->name ?? '-',
            $stock->product->category->name ?? '-',
            $stock->product->unit->name ?? '-',
            $stock->warehouse->code ?? '-',
            $stock->warehouse->name ?? '-',
            $stock->warehouse->branch->name ?? '-',
            $stock->quantity_on_hand ?? 0,
            $stock->average_cost ?? 0,
            $stock->stock_value ?? 0,
            $stock->moved_at?->format('Y-m-d H:i:s') ?? '-',
        ];
    }

    protected function actionClass(): string
    {
        return IndexInventoryValuationReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexInventoryValuationReportRequest::class;
    }
}
