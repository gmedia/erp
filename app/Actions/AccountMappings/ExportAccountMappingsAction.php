<?php

namespace App\Actions\AccountMappings;

use App\Actions\AccountMappings\Concerns\BuildsAccountMappingQuery;
use App\Domain\AccountMappings\AccountMappingFilterService;
use App\Exports\AccountMappingExport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportAccountMappingsAction
{
    use BuildsAccountMappingQuery;

    public function __construct(
        private AccountMappingFilterService $filterService
    ) {}

    public function execute(FormRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = $this->buildAccountMappingQuery(
            $this->filterService,
            [
                'type' => $validated['type'] ?? null,
                'source_coa_version_id' => $validated['source_coa_version_id'] ?? null,
                'target_coa_version_id' => $validated['target_coa_version_id'] ?? null,
            ],
            $request->filled('search') ? (string) ($validated['search'] ?? '') : null,
            (string) ($validated['sort_by'] ?? 'created_at'),
            (string) ($validated['sort_direction'] ?? 'desc'),
        );

        $filename = 'account_mappings_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new AccountMappingExport($validated, $query), $filePath, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ]);
    }
}
