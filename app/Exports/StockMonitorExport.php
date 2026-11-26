<?php

namespace App\Exports;

use App\Actions\StockMonitor\IndexStockMonitorAction;
use App\Exports\Concerns\AbstractActionCollectionExport;
use App\Exports\Concerns\MapsStockValuationExportRow;
use App\Http\Requests\StockMonitor\IndexStockMonitorRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting as Formats;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockMonitorExport extends AbstractActionCollectionExport implements Formats, WithHeadings, WithMapping
{
    use MapsStockValuationExportRow;

    public function headings(): array
    {
        return [
            'Product Code',
            'Product Name',
            'Category',
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
        return $this->stockValuationRow($stock);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function prepareFilters(array $filters): array
    {
        return array_merge($filters, [
            'per_page' => 100000,
        ]);
    }

    protected function actionClass(): string
    {
        return IndexStockMonitorAction::class;
    }

    protected function requestClass(): string
    {
        return IndexStockMonitorRequest::class;
    }

    protected function transformActionResult(mixed $result): Collection
    {
        return $result['stocks']->getCollection();
    }
}
