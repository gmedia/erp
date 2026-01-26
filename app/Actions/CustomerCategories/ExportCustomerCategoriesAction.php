<?php

namespace App\Actions\CustomerCategories;

use App\Domain\CustomerCategories\CustomerCategoryFilterService;
use App\Exports\CustomerCategoryExport;
use App\Http\Requests\CustomerCategories\ExportCustomerCategoryRequest;
use App\Models\CustomerCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Action to export customer categories to Excel based on filters
 */
class ExportCustomerCategoriesAction
{
    public function __construct(
        private CustomerCategoryFilterService $filterService
    ) {}

    /**
     * Execute the customer category export action.
     *
     * @param  \App\Http\Requests\CustomerCategories\ExportCustomerCategoryRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function execute(ExportCustomerCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = CustomerCategory::query();

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
        $filename = 'customer_categories_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Store the file in storage/app/public/exports/
        $filePath = 'exports/' . $filename;

        // Generate the Excel file using public disk
        $export = new CustomerCategoryExport([], $query);
        Excel::store($export, $filePath, 'public');

        // Generate the public URL for download
        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
