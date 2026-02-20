<?php

namespace App\Actions\Assets;

use App\Imports\AssetImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class ImportAssetsAction
{
    public function execute(UploadedFile $file): array
    {
        $importer = new AssetImport();
        
        Excel::import($importer, $file);
        
        return [
            'imported' => $importer->importedCount,
            'skipped' => $importer->skippedCount,
            'errors' => $importer->errors,
        ];
    }
}
