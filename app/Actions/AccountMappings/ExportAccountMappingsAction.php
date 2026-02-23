<?php

namespace App\Actions\AccountMappings;

use App\Domain\AccountMappings\AccountMappingFilterService;
use App\Exports\AccountMappingExport;
use App\Models\AccountMapping;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportAccountMappingsAction
{
    public function __construct(
        private AccountMappingFilterService $filterService
    ) {}

    public function execute(FormRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = AccountMapping::query()->with([
            'sourceAccount.coaVersion',
            'targetAccount.coaVersion',
        ]);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, (string) $validated['search']);
        }

        $this->filterService->applyAdvancedFilters($query, [
            'type' => $validated['type'] ?? null,
            'source_coa_version_id' => $validated['source_coa_version_id'] ?? null,
            'target_coa_version_id' => $validated['target_coa_version_id'] ?? null,
        ]);

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';

        $this->filterService->applySorting($query, $sortBy, $sortDirection);

        $filename = 'account_mappings_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new AccountMappingExport($validated, $query), $filePath, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ]);
    }
}
