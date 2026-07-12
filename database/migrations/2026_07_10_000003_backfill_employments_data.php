<?php

use App\Models\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create default company
        $companyId = DB::table('companies')->insertGetId([
            'name' => 'PT. Default Company',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 2. Map all existing branches to default company (only those without company_id)
        DB::table('branches')
            ->whereNull('company_id')
            ->update(['company_id' => $companyId]);

        // 3. Build branch_id -> company_id map for fallback
        $branchCompanyMap = DB::table('branches')
            ->pluck('company_id', 'id')
            ->toArray();

        // 4. Migrate employee data to employments
        $employees = DB::table('employees')->get(['id', 'department_id', 'position_id', 'branch_id', 'salary', 'hire_date', 'employment_status', 'termination_date']);
        $now = Carbon::now();

        $employments = [];
        foreach ($employees as $employee) {
            $companyId = $branchCompanyMap[$employee->branch_id] ?? $companyId;

            $terminationDate = $employee->termination_date;
            // Nullify sentinel values (9999-12-31) and future dates
            if ($terminationDate !== null) {
                $parsed = Carbon::parse($terminationDate);
                if ($parsed->isFuture() || $parsed->format('Y-m-d') === '9999-12-31') {
                    $terminationDate = null;
                } else {
                    $terminationDate = $parsed->format('Y-m-d');
                }
            }

            $employmentStatus = $employee->employment_status;
            if ($employmentStatus === null || $employmentStatus === '') {
                $employmentStatus = 'regular';
            }

            $hireDate = Carbon::parse($employee->hire_date)->format('Y-m-d');

            $employments[] = [
                'employee_id' => $employee->id,
                'company_id' => $companyId,
                'department_id' => $employee->department_id,
                'position_id' => $employee->position_id,
                'branch_id' => $employee->branch_id,
                'salary' => $employee->salary,
                'hire_date' => $hireDate,
                'termination_date' => $terminationDate,
                'employment_status' => $employmentStatus,
                'is_current' => $terminationDate === null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($employments)) {
            DB::table('employments')->insert($employments);
        }
    }

    public function down(): void
    {
        DB::table('employments')->truncate();
        DB::table('branches')->update(['company_id' => null]);
        DB::table('companies')->truncate();
    }
};
