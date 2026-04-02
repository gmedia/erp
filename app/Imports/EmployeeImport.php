<?php

namespace App\Imports;

use App\Imports\Concerns\InteractsWithImportRows;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
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
            $rowData = $this->rowToArray($row);

            // 1. Validate the row data
            if (! $this->validateImportRow($rowData, $rowNumber, [
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
            ])) {

                continue;
            }

            // 2. Resolve Foreign Keys
            $resolvedLookups = $this->resolveLookupAssignments($rowData, $rowNumber, [
                ['lookup' => $this->departments, 'source' => 'department', 'entity' => 'Department', 'target' => 'department_id'],
                ['lookup' => $this->positions, 'source' => 'position', 'entity' => 'Position', 'target' => 'position_id'],
                ['lookup' => $this->branches, 'source' => 'branch', 'entity' => 'Branch', 'target' => 'branch_id'],
            ]);

            if ($resolvedLookups === null) {
                continue;
            }

            // 3. Upsert Logic
            $this->performImportUpsert($rowNumber, function () use ($rowData, $resolvedLookups): void {
                Employee::updateOrCreate(
                    ['email' => $rowData['email']],
                    [
                        'employee_id' => $rowData['employee_id'],
                        'name' => $rowData['name'],
                        'phone' => $rowData['phone'],
                        'department_id' => $resolvedLookups['department_id'],
                        'position_id' => $resolvedLookups['position_id'],
                        'branch_id' => $resolvedLookups['branch_id'],
                        'salary' => $rowData['salary'] ?? null,
                        'hire_date' => $rowData['hire_date'],
                        'employment_status' => $rowData['employment_status'],
                        'termination_date' => $rowData['termination_date'] ?? null,
                    ]
                );
            });
        }
    }
}
