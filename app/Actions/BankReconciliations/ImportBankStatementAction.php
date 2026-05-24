<?php

namespace App\Actions\BankReconciliations;

use App\Imports\BankStatementImport;
use App\Models\BankReconciliation;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class ImportBankStatementAction
{
    public function execute(BankReconciliation $bankReconciliation, UploadedFile $file, array $mapping): array
    {
        $importer = new BankStatementImport($bankReconciliation, $mapping);

        Excel::import($importer, $file);

        return [
            'imported' => $importer->importedCount,
            'skipped' => $importer->skippedCount,
            'errors' => $importer->errors,
        ];
    }
}
