<?php

namespace App\Http\Controllers;

use App\Actions\PeriodClosings\ClosePeriodAction;
use App\Actions\PeriodClosings\ExportPeriodClosingsAction;
use App\Actions\PeriodClosings\IndexPeriodClosingsAction;
use App\Actions\PeriodClosings\ReopenPeriodAction;
use App\Http\Requests\PeriodClosings\ExportPeriodClosingRequest;
use App\Http\Requests\PeriodClosings\IndexPeriodClosingRequest;
use App\Http\Requests\PeriodClosings\StorePeriodClosingRequest;
use App\Http\Resources\PeriodClosings\PeriodClosingCollection;
use App\Http\Resources\PeriodClosings\PeriodClosingResource;
use App\Models\PeriodClosing;
use Illuminate\Http\JsonResponse;

class PeriodClosingController extends Controller
{
    public function index(IndexPeriodClosingRequest $request, IndexPeriodClosingsAction $action): JsonResponse
    {
        return (new PeriodClosingCollection($action->execute($request)))->response();
    }

    public function store(StorePeriodClosingRequest $request): JsonResponse
    {
        $periodClosing = PeriodClosing::create($request->validated() + ['status' => 'draft', 'net_income' => 0, 'created_by' => auth()->id()]);

        return (new PeriodClosingResource($periodClosing->load(['fiscalYear', 'retainedEarningsAccount', 'creator'])))->response()->setStatusCode(201);
    }

    public function show(PeriodClosing $periodClosing): JsonResponse
    {
        return (new PeriodClosingResource($periodClosing->load(['fiscalYear', 'closingJournalEntry', 'retainedEarningsAccount', 'closedBy', 'reopenedBy', 'creator'])))->response();
    }

    public function close(PeriodClosing $periodClosing, ClosePeriodAction $action): JsonResponse
    {
        return (new PeriodClosingResource($action->execute($periodClosing)))->response();
    }

    public function reopen(PeriodClosing $periodClosing, ReopenPeriodAction $action): JsonResponse
    {
        return (new PeriodClosingResource($action->execute($periodClosing)))->response();
    }

    public function export(ExportPeriodClosingRequest $request, ExportPeriodClosingsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
