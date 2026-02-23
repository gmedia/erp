<?php

namespace App\Actions\Pipelines;

use App\Exports\PipelineExport;
use App\Http\Requests\Pipelines\ExportPipelineRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportPipelinesAction
{
    public function execute(ExportPipelineRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'entity_type' => $validated['entity_type'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });

        $filename = 'pipelines_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new PipelineExport($filters), $filePath, 'public');

        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
