<?php

namespace App\Exports;

use App\Actions\Reports\IndexInventoryStocktakeVarianceReportAction;
use App\Exports\Concerns\AbstractActionCollectionExport;
use App\Http\Requests\Reports\IndexInventoryStocktakeVarianceReportRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryStocktakeVarianceExport extends AbstractActionCollectionExport implements WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'Stocktake Number',
            'Stocktake Date',
            'Product Code',
            'Product Name',
            'Category',
            'Warehouse',
            'Branch',
            'System Quantity',
            'Counted Quantity',
            'Variance',
            'Result',
            'Counted At',
            'Counted By',
        ];
    }

    public function map($row): array
    {
        return [
            $row->stocktake_number ?? '-',
            $row->stocktake_date?->format('Y-m-d') ?? '-',
            $row->product_code ?? '-',
            $row->product_name ?? '-',
            $row->category_name ?? '-',
            $row->warehouse_name ?? '-',
            $row->branch_name ?? '-',
            $row->system_quantity ?? 0,
            $row->counted_quantity ?? 0,
            $row->variance ?? 0,
            $row->result ?? '-',
            $row->counted_at?->format('Y-m-d H:i:s') ?? '-',
            $row->counted_by_name ?? '-',
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
        return IndexInventoryStocktakeVarianceReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexInventoryStocktakeVarianceReportRequest::class;
    }
}
