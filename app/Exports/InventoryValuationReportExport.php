<?php

namespace App\Exports;

use App\Actions\Reports\IndexInventoryValuationReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Exports\Concerns\MapsStockValuationExportRow;
use App\Http\Requests\Reports\IndexInventoryValuationReportRequest;
use Maatwebsite\Excel\Concerns\WithColumnFormatting as Formats;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryValuationReportExport extends AbstractReportIndexExport implements Formats, WithHeadings, WithMapping
{
    use MapsStockValuationExportRow;

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
        return $this->stockValuationRow($stock, includeUnit: true);
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
