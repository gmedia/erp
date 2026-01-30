<?php

namespace App\Actions\Employees;

use App\Exports\EmployeeExport;
use App\Http\Requests\Employees\ExportEmployeeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Action to export employees to Excel based on filters
 */
class ExportEmployeesAction
{
    /**
     * Execute the employee export action
     */
    public function execute(ExportEmployeeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Map request parameters to match EmployeeExport expectations
        $filters = [
            'search' => $validated['search'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'position_id' => $validated['position_id'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        // Remove null values
        $filters = array_filter($filters);

        // Generate filename with timestamp
        $filename = 'employees_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Store the file in storage/app/public/exports/
        $filePath = 'exports/' . $filename;

        // Generate the Excel file using public disk
        $export = new EmployeeExport($filters);
        Excel::store($export, $filePath, 'public');

        // Generate the public URL for download
        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
