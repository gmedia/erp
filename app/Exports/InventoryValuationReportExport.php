<?php

namespace App\Exports;

use App\Actions\Reports\IndexInventoryValuationReportAction;
use App\Http\Requests\Reports\IndexInventoryValuationReportRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryValuationReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->filters['export'] = true;
    }

    public function collection()
    {
        $action = app(IndexInventoryValuationReportAction::class);
        $request = new IndexInventoryValuationReportRequest;
        $request->merge($this->filters);

        return $action->execute($request);
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
