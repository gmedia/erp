<?php

namespace App\Imports;

use App\Imports\Concerns\InteractsWithImportRows;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\Position;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeeImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    use InteractsWithImportRows;

    public int $importedCount = 0;
    public int $skippedCount = 0; // Skipped due to duplicates (if we use skip mode) or other non-fatal reasons
    public array $errors = [];

    protected $departments;
    protected $positions;
    protected $branches;
    protected $companies;

    public function __construct()
    {
        [
            'departments' => $this->departments,
            'positions' => $this->positions,
            'branches' => $this->branches,
            'companies' => $this->companies,
        ] = $this->preloadLookupMaps([
            'departments' => ['model' => Department::class],
            'positions' => ['model' => Position::class],
            'branches' => ['model' => Branch::class],
            'companies' => ['model' => Company::class],
        ]);
    }

    public function collection(Collection $rows)
    {
        $this->processImportCollection(
            $rows,
            [
                'employee_id' => 'required|string',
                'name' => 'required|string|max:255',
                'email' => 'required|email', // We check uniqueness manually for upsert/skip logic
                'phone' => 'nullable|string|max:20',
                'department' => 'required|string',
                'position' => 'required|string',
                'branch' => 'required|string',
                'company' => 'nullable|string',
                'salary' => 'nullable|numeric|min:0',
                'hire_date' => 'required|date_format:Y-m-d',
                'employment_status' => 'required|string|in:regular,intern',
                'termination_date' => 'nullable|date_format:Y-m-d',
            ],
            [
                [
                    'lookup' => $this->departments,
                    'source' => 'department',
                    'entity' => 'Department',
                    'target' => 'department_id',
                ],
                [
                    'lookup' => $this->positions,
                    'source' => 'position',
                    'entity' => 'Position',
                    'target' => 'position_id',
                ],
                [
                    'lookup' => $this->branches,
                    'source' => 'branch',
                    'entity' => 'Branch',
                    'target' => 'branch_id',
                ],
                [
                    'lookup' => $this->companies,
                    'source' => 'company',
                    'entity' => 'Company',
                    'target' => 'company_id',
                    'required' => false,
                ],
            ],
            function (array $rowData, array $resolvedLookups): void {
                $employee = Employee::updateOrCreate(
                    ['email' => $rowData['email']],
                    [
                        'employee_id' => $rowData['employee_id'],
                        'name' => $rowData['name'],
                        'phone' => $rowData['phone'],
                    ]
                );

                $companyId = $resolvedLookups['company_id'] ?? $this->companies->first();

                // Mark any existing current employment as not current,
                // then create/update the new current employment
                Employment::where('employee_id', $employee->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false]);

                Employment::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'department_id' => $resolvedLookups['department_id'],
                        'position_id' => $resolvedLookups['position_id'],
                        'branch_id' => $resolvedLookups['branch_id'],
                    ],
                    [
                        'company_id' => $companyId,
                        'salary' => $rowData['salary'] ?? null,
                        'hire_date' => $rowData['hire_date'],
                        'employment_status' => $rowData['employment_status'],
                        'termination_date' => $rowData['termination_date'] ?? null,
                        'is_current' => true,
                    ]
                );
            }
        );
    }
}
