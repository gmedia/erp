<?php

namespace App\Exports;

use App\Actions\Reports\IndexBookValueDepreciationReportAction;
use App\Exports\Concerns\AbstractActionCollectionExport;
use App\Exports\Concerns\ExportsNumberFormats;
use App\Http\Requests\Reports\IndexBookValueDepreciationRequest;
use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting as Formats;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BookValueDepreciationExport extends AbstractActionCollectionExport implements Formats, WithHeadings, WithMapping
{
    use ExportsNumberFormats;

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

    /**
     * @param  Asset  $asset
     */
    public function map($asset): array
    {
        return [
            $asset->asset_code,
            $asset->name,
            $asset->category->name ?? '-',
            $asset->branch->name ?? '-',
            $asset->purchase_date->format('Y-m-d'),
            $this->numericValue($asset->purchase_cost),
            $this->numericValue($asset->salvage_value),
            $asset->useful_life_months,
            $this->numericValue($asset->accumulated_depreciation),
            $this->numericValue($asset->book_value),
        ];
    }

    protected function currencyColumnHeadings(): array
    {
        return ['Purchase Cost', 'Salvage Value', 'Accumulated Depreciation', 'Book Value'];
    }

    protected function actionClass(): string
    {
        return IndexBookValueDepreciationReportAction::class;
    }

    protected function requestClass(): string
    {
        return IndexBookValueDepreciationRequest::class;
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

    protected function transformActionResult(mixed $result): Collection
    {
        if ($result instanceof LengthAwarePaginator) {
            return collect($result->items());
        }

        return parent::transformActionResult($result);
    }
}
