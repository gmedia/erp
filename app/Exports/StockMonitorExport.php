<?php

namespace App\Exports;

use App\Actions\StockMonitor\IndexStockMonitorAction;
use App\Exports\Concerns\AbstractActionCollectionExport;
use App\Exports\Concerns\ExportsNumberFormats;
use App\Http\Requests\StockMonitor\IndexStockMonitorRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting as Formats;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockMonitorExport extends AbstractActionCollectionExport implements Formats, WithHeadings, WithMapping
{
    use ExportsNumberFormats;

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
        return [
            $stock->product->code ?? '-',
            $stock->product->name ?? '-',
            $stock->product->category->name ?? '-',
            $stock->warehouse->code ?? '-',
            $stock->warehouse->name ?? '-',
            $stock->warehouse->branch->name ?? '-',
            $this->numericValue($stock->quantity_on_hand) ?? 0,
            $this->numericValue($stock->average_cost) ?? 0,
            $this->numericValue($stock->stock_value) ?? 0,
            $stock->moved_at?->format('Y-m-d H:i:s') ?? '-',
        ];
    }

    protected function numericColumnHeadings(): array
    {
        return ['Quantity On Hand'];
    }

    protected function currencyColumnHeadings(): array
    {
        return ['Average Cost', 'Stock Value'];
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
