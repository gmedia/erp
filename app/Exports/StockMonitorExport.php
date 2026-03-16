<?php

namespace App\Exports;

use App\Actions\StockMonitor\IndexStockMonitorAction;
use App\Http\Requests\StockMonitor\IndexStockMonitorRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMonitorExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private readonly array $filters = [])
    {
        // No-op constructor; filters are stored via promoted property.
    }

    public function collection()
    {
        $action = app(IndexStockMonitorAction::class);
        $request = new IndexStockMonitorRequest;
        $request->merge(array_merge($this->filters, [
            'per_page' => 100000,
        ]));

        $result = $action->execute($request);

        return $result['stocks']->getCollection();
    }

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
            $stock->quantity_on_hand ?? 0,
            $stock->average_cost ?? 0,
            $stock->stock_value ?? 0,
            $stock->moved_at?->format('Y-m-d H:i:s') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
