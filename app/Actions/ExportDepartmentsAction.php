<?php

namespace App\Actions;

use App\Domain\DepartmentFilterService;
use App\Exports\DepartmentExport;
use App\Http\Requests\ExportDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Action to export departments to Excel based on filters
 */
class ExportDepartmentsAction
{
    public function __construct(
        private DepartmentFilterService $filterService
    ) {}

    /**
     * Execute the department export action
     */
    public function execute(ExportDepartmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Department::query();

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
        $filename = 'departments_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Store the file in storage/app/public/exports/
        $filePath = 'exports/' . $filename;

        // Generate the Excel file using public disk
        $export = new DepartmentExport([], $query);
        Excel::store($export, $filePath, 'public');

        // Generate the public URL for download
        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
