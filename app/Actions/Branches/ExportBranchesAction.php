<?php

namespace App\Actions\Branches;

use App\Domain\Branches\BranchFilterService;
use App\Exports\BranchExport;
use App\Http\Requests\Branches\ExportBranchRequest;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Action to export branches to Excel based on filters.
 */
class ExportBranchesAction
{
    public function __construct(
        private BranchFilterService $filterService
    ) {}

    /**
     * Execute the branch export action.
     *
     * @param  \App\Http\Requests\Branches\ExportBranchRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function execute(ExportBranchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Branch::query();

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $validated['search'], ['name']);
        }

        $this->filterService->applySorting(
            $query,
            $validated['sort_by'] ?? 'created_at',
            $validated['sort_direction'] ?? 'desc',
            ['id', 'name', 'created_at', 'updated_at']
        );

        // Generate filename with timestamp
        $filename = 'branches_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Store the file in storage/app/public/exports/
        $filePath = 'exports/' . $filename;

        // Generate the Excel file using public disk
        $export = new BranchExport([], $query);
        Excel::store($export, $filePath, 'public');

        // Generate the public URL for download
        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
