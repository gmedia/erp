<?php

namespace App\Actions\Concerns;

use App\Support\ExportStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

abstract class ConfiguredWriterExportAction
{
    public function execute(FormRequest $request): JsonResponse
    {
        $filters = $this->buildFilters($request->validated());
        [$extension, $writerType] = $this->resolveExportFormat($request);
        $filename = $this->buildFilename($extension);
        $filePath = 'exports/' . $filename;

        ExportStorage::store($this->makeExport($filters), $filePath, $writerType);

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
        return array_filter($validated, static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolveExportFormat(FormRequest $request): array
    {
        if (! $this->supportsCsvExport()) {
            return ['xlsx', ExcelFormat::XLSX];
        }

        if ($request->input('format', 'xlsx') === 'csv') {
            return ['csv', ExcelFormat::CSV];
        }

        return ['xlsx', ExcelFormat::XLSX];
    }

    protected function buildFilename(string $extension): string
    {
        return $this->filenamePrefix()
            . '_' . now()->format($this->timestampFormat())
            . '_' . Str::ulid()
            . '.' . $extension;
    }

    protected function supportsCsvExport(): bool
    {
        return true;
    }

    protected function timestampFormat(): string
    {
        return 'Y-m-d_H-i-s';
    }

    abstract protected function filenamePrefix(): string;

    /**
     * @param  array<string, mixed>  $filters
     */
    abstract protected function makeExport(array $filters): object;
}
