<?php

namespace App\Http\Controllers;

use App\Actions\Reports\ExportBookValueDepreciationReportAction;
use App\Actions\Reports\IndexBookValueDepreciationReportAction;
use App\Http\Requests\Reports\ExportBookValueDepreciationRequest;
use App\Http\Requests\Reports\IndexBookValueDepreciationRequest;
use App\Http\Resources\Reports\BookValueDepreciationCollection;
use Illuminate\Http\JsonResponse;

class BookValueDepreciationReportController extends Controller
{
    /**
     * Display the Book Value & Depreciation report.
     */
    public function index(IndexBookValueDepreciationRequest $request, IndexBookValueDepreciationReportAction $action): BookValueDepreciationCollection
    {
        $assets = $action->execute($request);

        return new BookValueDepreciationCollection($assets);
    }

    /**
     * Export the Book Value & Depreciation report to Excel/CSV structure.
     */
    public function export(ExportBookValueDepreciationRequest $request, ExportBookValueDepreciationReportAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
