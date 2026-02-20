<?php

namespace App\Actions\Suppliers;

use App\Imports\SupplierImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class ImportSuppliersAction
{
    /**
     * Execute the import process.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return array
     */
    public function execute(UploadedFile $file): array
    {
        $importer = new SupplierImport();
        
        // Import the file using the importer class
        Excel::import($importer, $file);

        // Return the summary
        return [
            'imported' => $importer->importedCount,
            'skipped' => $importer->skippedCount,
            'errors' => $importer->errors,
        ];
    }
}
