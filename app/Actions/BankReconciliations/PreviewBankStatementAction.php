<?php

namespace App\Actions\BankReconciliations;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;

class PreviewBankStatementAction
{
    public function execute(UploadedFile $file): array
    {
        $data = Excel::toArray(new class implements WithHeadingRow {}, $file);

        $rows = $data[0] ?? [];
        $headers = ! empty($rows) ? array_keys($rows[0]) : [];
        $previewRows = array_slice($rows, 0, 5);

        return [
            'headers' => $headers,
            'preview_rows' => $previewRows,
        ];
    }
}
