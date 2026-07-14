<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesBranchScope;
use App\Models\Asset;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ResolvesBranchScope;

    public function index(): JsonResponse
    {
        $branchId = $this->resolveBranchScope(null);

        return response()->json([
            'data' => [
                'totals' => [
                    'customers' => $this->scoped(Customer::query(), $branchId, Customer::class)->count(),
                    'employees' => $this->scoped(Employee::query(), $branchId, Employee::class)->count(),
                    'suppliers' => $this->scoped(Supplier::query(), $branchId, Supplier::class)->count(),
                    'assets' => $this->scoped(Asset::query(), $branchId, Asset::class)->count(),
                ],
            ],
        ]);
    }
}
