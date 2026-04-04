<?php

namespace App\Actions\Concerns;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

abstract class ConfiguredFormattedExportAction
{
    public function execute(FormRequest $request): JsonResponse
    {
        $filters = $this->buildFilters($request->validated());
        [$extension, $writerType] = $this->resolveExportFormat($request);
        $filename = $this->buildFilename($extension);
        $filePath = 'exports/' . $filename;

        Excel::store($this->makeExport($filters), $filePath, 'public', $writerType);

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
        return $this->filenamePrefix() . '_' . now()->format($this->timestampFormat()) . '_' . Str::ulid() . '.' . $extension;
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
