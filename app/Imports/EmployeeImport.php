<?php

namespace App\Imports;

use App\Imports\Concerns\InteractsWithImportRows;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
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

    public function __construct()
    {
        // Pre-load all potential lookups to minimize queries
        $this->departments = Department::pluck('id', 'name'); // name => id
        $this->positions = Position::pluck('id', 'name');     // name => id
        $this->branches = Branch::pluck('id', 'name');        // name => id
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +1 for 0-index, +1 for heading row

            // 1. Validate the row data
            $validator = Validator::make($row->toArray(), [
                'employee_id' => 'required|string',
                'name' => 'required|string|max:255',
                'email' => 'required|email', // We check uniqueness manually for upsert/skip logic
                'phone' => 'nullable|string|max:20',
                'department' => 'required|string',
                'position' => 'required|string',
                'branch' => 'required|string',
                'salary' => 'nullable|numeric|min:0',
                'hire_date' => 'required|date_format:Y-m-d',
                'employment_status' => 'required|string|in:regular,intern',
                'termination_date' => 'nullable|date_format:Y-m-d',
            ]);

            if ($validator->fails()) {
                $this->recordValidationErrors($validator, $rowNumber);

                continue;
            }

            // 2. Resolve Foreign Keys
            $departmentId = $this->resolveLookupId($this->departments, $row['department'], $rowNumber, 'department', 'Department');
            if ($departmentId === null) {
                continue;
            }

            $positionId = $this->resolveLookupId($this->positions, $row['position'], $rowNumber, 'position', 'Position');
            if ($positionId === null) {
                continue;
            }

            $branchId = $this->resolveLookupId($this->branches, $row['branch'], $rowNumber, 'branch', 'Branch');
            if ($branchId === null) {
                continue;
            }

            // 3. Upsert Logic
            $this->performImportUpsert($rowNumber, function () use ($row, $departmentId, $positionId, $branchId): void {
                Employee::updateOrCreate(
                    ['email' => $row['email']],
                    [
                        'employee_id' => $row['employee_id'],
                        'name' => $row['name'],
                        'phone' => $row['phone'],
                        'department_id' => $departmentId,
                        'position_id' => $positionId,
                        'branch_id' => $branchId,
                        'salary' => $row['salary'] ?? null,
                        'hire_date' => $row['hire_date'],
                        'employment_status' => $row['employment_status'],
                        'termination_date' => $row['termination_date'] ?? null,
                    ]
                );
            });
        }
    }
}
