<?php

namespace App\Actions\Concerns;

use App\Support\ExportStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

trait ExportsReportToExcel
{
    public function execute(array $filters): JsonResponse
    {
        $filename = $this->filenamePrefix() . '_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.xlsx';
        $path = 'exports/' . $filename;
        ExportStorage::store($this->makeExport($filters), $path);

        return response()->json(['url' => ExportStorage::url($path), 'filename' => $filename]);
    }

    abstract protected function filenamePrefix(): string;

    abstract protected function makeExport(array $filters): object;
}
