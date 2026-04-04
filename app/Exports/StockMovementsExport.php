<?php

namespace App\Exports;

use App\Actions\StockMovements\IndexStockMovementsAction;
use App\Exports\Concerns\AbstractActionCollectionExport;
use App\Http\Requests\StockMovements\IndexStockMovementRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StockMovementsExport extends AbstractActionCollectionExport implements WithHeadings, WithMapping
{
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
            $movement->product->code ?? '-',
            $movement->product->name ?? '-',
            $movement->warehouse->code ?? '-',
            $movement->warehouse->name ?? '-',
            $movement->warehouse->branch->name ?? '-',
            $movement->movement_type ?? '-',
            $movement->reference_number ?? '-',
            $movement->quantity_in ?? 0,
            $movement->quantity_out ?? 0,
            $movement->balance_after ?? 0,
            $movement->unit_cost ?? '-',
            $movement->average_cost_after ?? '-',
            $movement->notes ?? '-',
            $movement->createdBy->name ?? '-',
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
        return IndexStockMovementsAction::class;
    }

    protected function requestClass(): string
    {
        return IndexStockMovementRequest::class;
    }
}
