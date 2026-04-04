<?php

namespace App\Exports;

use App\Actions\AssetStocktakes\IndexAssetStocktakeVarianceAction;
use App\Exports\Concerns\AbstractActionCollectionExport;
use App\Http\Requests\AssetStocktakes\IndexAssetStocktakeVarianceRequest;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetStocktakeVarianceExport extends AbstractActionCollectionExport implements WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function headings(): array
    {
        return [
            'No',
            'Stocktake Reference',
            'Asset Code',
            'Asset Name',
            'Expected Branch',
            'Expected Location',
            'Found Branch',
            'Found Location',
            'Result',
            'Notes',
            'Checked At',
            'Checked By',
        ];
    }

    public function map($item): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $item->stocktake->reference,
            $item->asset->asset_code,
            $item->asset->name,
            $item->expectedBranch->name ?? '-',
            $item->expectedLocation->name ?? '-',
            $item->foundBranch->name ?? '-',
            $item->foundLocation->name ?? '-',
            ucfirst($item->result),
            $item->notes ?? '-',
            $item->checked_at?->format('Y-m-d H:i:s') ?? '-',
            $item->checkedBy->name ?? '-',
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
        return IndexAssetStocktakeVarianceAction::class;
    }

    protected function requestClass(): string
    {
        return IndexAssetStocktakeVarianceRequest::class;
    }
}
