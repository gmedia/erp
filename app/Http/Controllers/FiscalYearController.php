<?php

namespace App\Http\Controllers;

use App\Actions\FiscalYears\ExportFiscalYearsAction;
use App\Actions\FiscalYears\IndexFiscalYearsAction;
use App\Http\Requests\FiscalYears\ExportFiscalYearRequest;
use App\Http\Requests\FiscalYears\IndexFiscalYearRequest;
use App\Http\Requests\FiscalYears\StoreFiscalYearRequest;
use App\Http\Requests\FiscalYears\UpdateFiscalYearRequest;
use App\Http\Resources\FiscalYears\FiscalYearCollection;
use App\Http\Resources\FiscalYears\FiscalYearResource;
use App\Models\FiscalYear;
use Illuminate\Http\JsonResponse;

/**
 * Controller for fiscal year management operations.
 */
class FiscalYearController extends Controller
{
    /**
     * Display a listing of the fiscal years.
     */
    public function index(IndexFiscalYearRequest $request): JsonResponse
    {
        $fiscalYears = (new IndexFiscalYearsAction())->execute($request);

        return (new FiscalYearCollection($fiscalYears))->response();
    }

    /**
     * Store a newly created fiscal year in storage.
     */
    public function store(StoreFiscalYearRequest $request): JsonResponse
    {
        $fiscalYear = FiscalYear::create($request->validated());

        return (new FiscalYearResource($fiscalYear))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified fiscal year.
     */
    public function show(FiscalYear $fiscalYear): JsonResponse
    {
        return (new FiscalYearResource($fiscalYear))->response();
    }

    /**
     * Update the specified fiscal year in storage.
     */
    public function update(UpdateFiscalYearRequest $request, FiscalYear $fiscalYear): JsonResponse
    {
        $fiscalYear->update($request->validated());

        return (new FiscalYearResource($fiscalYear))->response();
    }

    /**
     * Remove the specified fiscal year from storage.
     */
    public function destroy(FiscalYear $fiscalYear): JsonResponse
    {
        $fiscalYear->delete();

        return response()->json(null, 204);
    }

    /**
     * Export fiscal years to Excel based on filters.
     */
    public function export(ExportFiscalYearRequest $request): JsonResponse
    {
        return (new ExportFiscalYearsAction())->execute($request);
    }
}
