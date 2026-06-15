<?php

namespace App\Http\Controllers;

use App\Actions\AgingDashboard\GetAgingDashboardDataAction;
use App\Http\Controllers\Concerns\ResolvesBranchScope;
use App\Models\Branch;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AgingDashboardController extends Controller
{
    use ResolvesBranchScope;

    public function __invoke(
        Request $request,
        GetAgingDashboardDataAction $action,
    ): JsonResponse {
        $asOfDate = $this->resolveAsOfDate($request->query('as_of_date'));
        $branchId = $this->resolveBranchFromRequest($request);

        $branches = Branch::orderBy('name')->get(['id', 'name']);

        $data = $action->execute($asOfDate, $branchId);

        return response()->json([
            'as_of_date' => $asOfDate,
            'branches' => $branches,
            'selected_branch_id' => $branchId,
            ...$data,
        ]);
    }

    private function resolveAsOfDate(mixed $input): string
    {
        if (! is_string($input) || $input === '') {
            return Carbon::today()->toDateString();
        }

        try {
            return Carbon::parse($input)->toDateString();
        } catch (InvalidFormatException) {
            return Carbon::today()->toDateString();
        }
    }
}
