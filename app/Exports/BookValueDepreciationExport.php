<?php

namespace App\Exports;

use App\Actions\Reports\IndexBookValueDepreciationReportAction;
use App\Http\Requests\Reports\IndexBookValueDepreciationRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BookValueDepreciationExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // Re-use logic from Index action to generate exactly what is shown on screen
        $action = app(IndexBookValueDepreciationReportAction::class);

        // Build a fake request from the filters
        $request = new IndexBookValueDepreciationRequest();
        $request->merge($this->filters);

        return $action->execute($request);
    }

    public function headings(): array
    {
        return [
            'Asset Code',
            'Name',
            'Category',
            'Branch',
            'Purchase Date',
            'Purchase Cost',
            'Salvage Value',
            'Useful Life (Months)',
            'Accumulated Depreciation',
            'Book Value',
        ];
    }

    public function map($asset): array
    {
        return [
            $asset->asset_code,
            $asset->name,
            $asset->category?->name ?? '-',
            $asset->branch?->name ?? '-',
            $asset->purchase_date?->format('Y-m-d') ?? '-',
            $asset->purchase_cost,
            $asset->salvage_value,
            $asset->useful_life_months,
            $asset->accumulated_depreciation,
            $asset->book_value,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
