<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportBookValueDepreciationReportAction;
use App\Actions\Reports\IndexBookValueDepreciationReportAction;
use App\Http\Requests\Reports\ExportBookValueDepreciationRequest;
use App\Http\Requests\Reports\IndexBookValueDepreciationRequest;
use App\Http\Resources\Reports\BookValueDepreciationCollection;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class BookValueDepreciationReportController extends Controller
{
    /**
     * Display the Book Value & Depreciation report.
     */
    public function index(IndexBookValueDepreciationRequest $request, IndexBookValueDepreciationReportAction $action): Response|BookValueDepreciationCollection
    {
        $assets = $action->execute($request);

        if ($request->wantsJson()) {
            return new BookValueDepreciationCollection($assets);
        }

        return Inertia::render('reports/book-value-depreciation/index', [
            'assets' => new BookValueDepreciationCollection($assets),
            'filters' => $request->only([
                'search',
                'asset_category_id',
                'branch_id',
                'sort_by',
                'sort_direction',
            ]),
        ]);
    }

    /**
     * Export the Book Value & Depreciation report to Excel/CSV structure.
     */
    public function export(ExportBookValueDepreciationRequest $request, ExportBookValueDepreciationReportAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
