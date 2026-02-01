<?php

namespace App\Actions\Accounts;

use App\Exports\AccountExport;
use App\Http\Requests\Accounts\ExportAccountRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportAccountsAction
{
    public function execute(ExportAccountRequest $request): JsonResponse
    {
        $filters = $request->validated();
        
        $filename = 'accounts_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        $export = new AccountExport($filters);
        Excel::store($export, $filePath, 'public');

        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
