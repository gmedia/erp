<?php

namespace App\Actions\Concerns;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

abstract class ConfiguredXlsxExportAction
{
    public function execute(FormRequest $request): JsonResponse
    {
        $filters = $this->buildFilters($request->validated());
        $filename = $this->buildFilename();
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
        return array_filter($validated, static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    protected function buildFilename(): string
    {
        $segments = [
            $this->filenamePrefix(),
            'export',
            now()->format($this->timestampFormat()),
        ];

        if ($this->includeUlidInFilename()) {
            $segments[] = (string) Str::ulid();
        }

        return implode($this->filenameDelimiter(), $segments) . '.xlsx';
    }

    protected function filenameDelimiter(): string
    {
        return '_';
    }

    protected function timestampFormat(): string
    {
        return 'Y-m-d_H-i-s';
    }

    protected function includeUlidInFilename(): bool
    {
        return false;
    }

    abstract protected function filenamePrefix(): string;

    /**
     * @param  array<string, mixed>  $filters
     */
    abstract protected function makeExport(array $filters): object;
}
