<?php

namespace App\Actions\Reports;

use App\Exports\GeneralLedgerReportExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportGeneralLedgerReportAction
{
    public function execute(array $filters): JsonResponse
    {
        $filename = 'general_ledger_report_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.xlsx';
        $path = 'exports/' . $filename;
        Excel::store(new GeneralLedgerReportExport($filters), $path, 'public');

        return response()->json(['url' => Storage::disk('public')->url($path), 'filename' => $filename]);
    }
}
