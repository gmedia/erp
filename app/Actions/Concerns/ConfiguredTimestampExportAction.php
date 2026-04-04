<?php

namespace App\Actions\Concerns;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

abstract class ConfiguredTimestampExportAction
{
    public function execute(FormRequest $request): JsonResponse
    {
        $filters = $this->buildFilters($request->validated());
        $filename = $this->filenamePrefix() . '_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store($this->makeExport($filters), $filePath, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function buildFilters(array $validated): array
    {
        $filters = [];

        foreach ($this->filterDefaults() as $key => $default) {
            $filters[$key] = $validated[$key] ?? $default;
        }

        return array_filter($filters, static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function filterDefaults(): array;

    abstract protected function filenamePrefix(): string;

    /**
     * @param  array<string, mixed>  $filters
     */
    abstract protected function makeExport(array $filters): object;
}
