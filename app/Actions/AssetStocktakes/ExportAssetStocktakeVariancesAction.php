<?php

namespace App\Actions\AssetStocktakes;

use App\Http\Requests\AssetStocktakes\ExportAssetStocktakeVarianceRequest;
use App\Models\AssetStocktakeItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportAssetStocktakeVariancesAction
{
    public function execute(ExportAssetStocktakeVarianceRequest $request): StreamedResponse
    {
        $query = AssetStocktakeItem::query()
            ->with([
                'stocktake',
                'asset',
                'expectedBranch',
                'expectedLocation',
                'foundBranch',
                'foundLocation',
                'checkedBy',
            ])
            ->whereIn('result', ['missing', 'damaged', 'moved']);

        if ($request->filled('asset_stocktake_id')) {
            $query->where('asset_stocktake_id', $request->get('asset_stocktake_id'));
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('stocktake', function (Builder $q) use ($request) {
                $q->where('branch_id', $request->get('branch_id'));
            });
        }

        if ($request->filled('result')) {
            $query->where('result', $request->get('result'));
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('asset', function (Builder $q) use ($search) {
                $q->where('asset_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'checked_at');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['id', 'result', 'checked_at'])) {
            $query->orderBy($sortBy, $sortDirection);
        } elseif ($sortBy === 'asset_code' || $sortBy === 'asset_name') {
            $query->orderBy(
                \App\Models\Asset::select($sortBy === 'asset_code' ? 'asset_code' : 'name')
                    ->whereColumn('assets.id', 'asset_stocktake_items.asset_id'),
                $sortDirection
            );
        } else {
            $query->orderBy('checked_at', 'desc');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Define Headers
        $headers = [
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

        foreach (array_values($headers) as $index => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
            $sheet->getStyle($column . '1')->getFont()->setBold(true);
        }

        $row = 2;
        $no = 1;
        $query->chunk(100, function ($items) use ($sheet, &$row, &$no) {
            foreach ($items as $item) {
                $sheet->setCellValue('A' . $row, $no++);
                $sheet->setCellValue('B' . $row, $item->stocktake?->reference ?? '-');
                $sheet->setCellValue('C' . $row, $item->asset?->asset_code ?? '-');
                $sheet->setCellValue('D' . $row, $item->asset?->name ?? '-');
                $sheet->setCellValue('E' . $row, $item->expectedBranch?->name ?? '-');
                $sheet->setCellValue('F' . $row, $item->expectedLocation?->name ?? '-');
                $sheet->setCellValue('G' . $row, $item->foundBranch?->name ?? '-');
                $sheet->setCellValue('H' . $row, $item->foundLocation?->name ?? '-');
                $sheet->setCellValue('I' . $row, ucfirst($item->result));
                $sheet->setCellValue('J' . $row, $item->notes ?? '-');
                $sheet->setCellValue('K' . $row, $item->checked_at ? $item->checked_at->format('Y-m-d H:i:s') : '-');
                $sheet->setCellValue('L' . $row, $item->checkedBy?->name ?? '-');
                $row++;
            }
        });

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'asset_stocktake_variances_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
