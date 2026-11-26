<?php

namespace App\Exports;

use App\Actions\Reports\IndexInventoryValuationReportAction;
use App\Exports\Concerns\AbstractReportIndexExport;
use App\Exports\Concerns\ExportsNumberFormats;
use App\Http\Requests\Reports\IndexInventoryValuationReportRequest;
use Maatwebsite\Excel\Concerns\WithColumnFormatting as Formats;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryValuationReportExport extends AbstractReportIndexExport implements Formats, WithHeadings, WithMapping
{
    use ExportsNumberFormats;

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return $this->buildColumnFormats($this->headings());
    }

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

    protected function actionClass(): string
    {
        return IndexInventoryValuationReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexInventoryValuationReportRequest::class;
    }
}
