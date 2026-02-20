<?php

namespace App\Imports;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class EmployeeImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
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
                foreach ($validator->errors()->all() as $error) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'field' => 'Validation',
                        'message' => $error,
                    ];
                }
                continue;
            }

            // 2. Resolve Foreign Keys
            $departmentId = $this->departments->get($row['department']);
            if (!$departmentId) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'field' => 'department',
                    'message' => "Department '{$row['department']}' not found.",
                ];
                continue;
            }

            $positionId = $this->positions->get($row['position']);
            if (!$positionId) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'field' => 'position',
                    'message' => "Position '{$row['position']}' not found.",
                ];
                continue;
            }

            $branchId = $this->branches->get($row['branch']);
            if (!$branchId) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'field' => 'branch',
                    'message' => "Branch '{$row['branch']}' not found.",
                ];
                continue;
            }

            // 3. Upsert Logic
            try {
                $employee = Employee::updateOrCreate(
                    ['email' => $row['email']], // Look up by email
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
                        // 'user_id' => null, // Optional: logic to create/link user
                    ]
                );

                if ($employee->wasRecentlyCreated) {
                    $this->importedCount++;
                } else {
                    // It was updated
                    $this->importedCount++; 
                }
            } catch (\Exception $e) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'field' => 'System',
                    'message' => "Failed to save: " . $e->getMessage(),
                ];
            }
        }
    }
}
