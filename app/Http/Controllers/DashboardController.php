<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Supplier;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard', [
            'totals' => [
                'customers' => Customer::count(),
                'employees' => Employee::count(),
                'suppliers' => Supplier::count(),
                'assets' => Asset::count(),
            ],
        ]);
    }
}

