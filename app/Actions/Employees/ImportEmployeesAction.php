<?php

namespace App\Actions\Employees;

use App\Imports\EmployeeImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class ImportEmployeesAction
{
    /**
     * Execute the import process.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return array
     */
    public function execute(UploadedFile $file): array
    {
        $importer = new EmployeeImport();
        
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
