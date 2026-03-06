<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'totals' => [
                    'customers' => Customer::count(),
                    'employees' => Employee::count(),
                    'suppliers' => Supplier::count(),
                    'assets' => Asset::count(),
                ],
            ],
        ]);
    }
}
