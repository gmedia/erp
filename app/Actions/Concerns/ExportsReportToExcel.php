<?php

namespace App\Actions\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

trait ExportsReportToExcel
{
    public function execute(array $filters): JsonResponse
    {
        $filename = $this->filenamePrefix() . '_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.xlsx';
        $path = 'exports/' . $filename;
        Excel::store($this->makeExport($filters), $path, 'public');

        return response()->json(['url' => Storage::disk('public')->url($path), 'filename' => $filename]);
    }

    abstract protected function filenamePrefix(): string;

    abstract protected function makeExport(array $filters): object;
}
