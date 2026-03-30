<?php

namespace App\Actions\AssetStocktakes;

use App\Domain\AssetStocktakes\AssetStocktakeVarianceQueryService;
use App\Http\Requests\AssetStocktakes\ExportAssetStocktakeVarianceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportAssetStocktakeVariancesAction
{
    public function __construct(
        private readonly AssetStocktakeVarianceQueryService $queryService
    ) {}

    public function execute(ExportAssetStocktakeVarianceRequest $request): JsonResponse
    {
        $query = $this->queryService->buildBaseQuery();
        $this->queryService->applyFilters($query, [
            'asset_stocktake_id' => $request->get('asset_stocktake_id'),
            'branch_id' => $request->get('branch_id'),
            'result' => $request->get('result'),
            'search' => $request->get('search'),
        ]);

        $sortBy = $request->get('sort_by', 'checked_at');
        $sortDirection = (string) $request->get('sort_direction', 'desc');
        $this->queryService->applySorting($query, $sortBy, $sortDirection);

        $spreadsheet = new Spreadsheet;
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

        foreach ($headers as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
            $sheet->getStyle($column . '1')->getFont()->setBold(true);
        }

        $row = 2;
        $no = 1;
        $query->chunk(100, function ($items) use ($sheet, &$row, &$no) {
            foreach ($items as $item) {
                $sheet->setCellValue('A' . $row, $no++);
                $sheet->setCellValue('B' . $row, $item->stocktake->reference);
                $sheet->setCellValue('C' . $row, $item->asset->asset_code);
                $sheet->setCellValue('D' . $row, $item->asset->name);
                $sheet->setCellValue('E' . $row, $item->expectedBranch->name ?? '-');
                $sheet->setCellValue('F' . $row, $item->expectedLocation->name ?? '-');
                $sheet->setCellValue('G' . $row, $item->foundBranch->name ?? '-');
                $sheet->setCellValue('H' . $row, $item->foundLocation->name ?? '-');
                $sheet->setCellValue('I' . $row, ucfirst($item->result));
                $sheet->setCellValue('J' . $row, $item->notes ?? '-');
                $sheet->setCellValue('K' . $row, $item->checked_at ? $item->checked_at->format('Y-m-d H:i:s') : '-');
                $sheet->setCellValue('L' . $row, $item->checkedBy->name ?? '-');
                $row++;
            }
        });

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'asset_stocktake_variances_' . date('Ymd_His') . '.xlsx';
        $filePath = 'exports/' . $fileName;

        if (! Storage::disk('public')->exists('exports')) {
            Storage::disk('public')->makeDirectory('exports');
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/public/' . $filePath));

        return response()->json([
            'url' => Storage::disk('public')->url($filePath),
            'filename' => $fileName,
        ]);
    }
}
