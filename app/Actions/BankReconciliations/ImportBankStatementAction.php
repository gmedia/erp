<?php

namespace App\Actions\BankReconciliations;

use App\Imports\BankStatementImport;
use App\Models\BankReconciliation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ImportBankStatementAction
{
    /**
     * @param  array<string, mixed>  $mapping
     * @return array<string, mixed>
     */
    public function execute(BankReconciliation $bankReconciliation, UploadedFile $file, array $mapping): array
    {
        return DB::transaction(function () use ($bankReconciliation, $file, $mapping): array {
            $importer = new BankStatementImport($bankReconciliation, $mapping);

            Excel::import($importer, $file);

            $bankReconciliation->recalculateBalances();

            return [
                'imported' => $importer->importedCount,
                'skipped' => $importer->skippedCount,
                'errors' => $importer->errors,
            ];
        });
    }
}
