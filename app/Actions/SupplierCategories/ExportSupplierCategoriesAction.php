<?php

namespace App\Actions\SupplierCategories;

use App\Domain\SupplierCategories\SupplierCategoryFilterService;
use App\Exports\SupplierCategoryExport;
use App\Http\Requests\SupplierCategories\ExportSupplierCategoryRequest;
use App\Models\SupplierCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Action to export supplier categories to Excel based on filters
 */
class ExportSupplierCategoriesAction
{
    public function __construct(
        private SupplierCategoryFilterService $filterService
    ) {}

    /**
     * Execute the supplier category export action.
     *
     * @param  \App\Http\Requests\SupplierCategories\ExportSupplierCategoryRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function execute(ExportSupplierCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = SupplierCategory::query();

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
        $filename = 'supplier_categories_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Store the file in storage/app/public/exports/
        $filePath = 'exports/' . $filename;

        // Generate the Excel file using public disk
        $export = new SupplierCategoryExport([], $query);
        Excel::store($export, $filePath, 'public');

        // Generate the public URL for download
        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
