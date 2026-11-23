<?php

namespace App\Actions\Concerns;

use App\Support\ExportStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

abstract class ConfiguredTransactionExportAction
{
    public function execute(FormRequest $request): JsonResponse
    {
        $filters = $this->buildFilters($request->validated());
        $filename = $this->filenamePrefix() . '_export_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.xlsx';
        $filePath = 'exports/' . $filename;

        ExportStorage::store($this->makeExport($filters), $filePath);

        return response()->json([
            'url' => ExportStorage::url($filePath),
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

        return array_filter($filters, static fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [];
    }

    abstract protected function filenamePrefix(): string;

    /**
     * @param  array<string, mixed>  $filters
     */
    abstract protected function makeExport(array $filters): object;
}
