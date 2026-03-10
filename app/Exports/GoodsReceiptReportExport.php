<?php

namespace App\Exports;

use App\Actions\Reports\IndexGoodsReceiptReportAction;
use App\Http\Requests\Reports\IndexGoodsReceiptReportRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GoodsReceiptReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->filters['export'] = true;
    }

    public function collection()
    {
        $action = app(IndexGoodsReceiptReportAction::class);
        $request = new IndexGoodsReceiptReportRequest();
        $request->merge($this->filters);

        return $action->execute($request);
    }

    public function headings(): array
    {
        return [
            'GR Number',
            'Receipt Date',
            'Status',
            'PO Number',
            'Supplier',
            'Warehouse',
            'Item Count',
            'Total Received Qty',
            'Total Accepted Qty',
            'Total Rejected Qty',
            'Total Receipt Value',
        ];
    }

    public function map($row): array
    {
        return [
            $row->gr_number ?? '-',
            $row->receipt_date?->format('Y-m-d') ?? '-',
            $row->status ?? '-',
            $row->po_number ?? '-',
            $row->supplier_name ?? '-',
            $row->warehouse_name ?? '-',
            $row->item_count ?? 0,
            $row->total_received_quantity ?? 0,
            $row->total_accepted_quantity ?? 0,
            $row->total_rejected_quantity ?? 0,
            $row->total_receipt_value ?? 0,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
