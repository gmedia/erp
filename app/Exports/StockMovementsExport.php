<?php

namespace App\Exports;

use App\Actions\StockMovements\IndexStockMovementsAction;
use App\Http\Requests\StockMovements\IndexStockMovementRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockMovementsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->filters['export'] = true;
    }

    public function collection()
    {
        $action = app(IndexStockMovementsAction::class);

        $request = new IndexStockMovementRequest();
        $request->merge($this->filters);

        return $action->execute($request);
    }

    public function headings(): array
    {
        return [
            'Moved At',
            'Product Code',
            'Product Name',
            'Warehouse Code',
            'Warehouse Name',
            'Branch',
            'Movement Type',
            'Reference',
            'Qty In',
            'Qty Out',
            'Balance After',
            'Unit Cost',
            'Average Cost After',
            'Notes',
            'Created By',
        ];
    }

    public function map($movement): array
    {
        return [
            $movement->moved_at?->format('Y-m-d H:i:s') ?? '-',
            $movement->product?->code ?? '-',
            $movement->product?->name ?? '-',
            $movement->warehouse?->code ?? '-',
            $movement->warehouse?->name ?? '-',
            $movement->warehouse?->branch?->name ?? '-',
            $movement->movement_type ?? '-',
            $movement->reference_number ?? '-',
            $movement->quantity_in ?? 0,
            $movement->quantity_out ?? 0,
            $movement->balance_after ?? 0,
            $movement->unit_cost ?? '-',
            $movement->average_cost_after ?? '-',
            $movement->notes ?? '-',
            $movement->createdBy?->name ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

