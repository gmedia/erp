<?php

namespace App\Imports;

use App\Imports\Concerns\InteractsWithImportRows;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\AssetModel;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    use InteractsWithImportRows;

    public int $importedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];

    protected $categories;
    protected $models;
    protected $branches;
    protected $locations;
    protected $departments;
    protected $employees;
    protected $suppliers;

    public function __construct()
    {
        // Pre-load all potential lookups to minimize queries
        $this->categories = AssetCategory::pluck('id', 'name');
        $this->models = AssetModel::pluck('id', 'model_name');
        $this->branches = Branch::pluck('id', 'name');
        $this->locations = AssetLocation::pluck('id', 'name');
        $this->departments = Department::pluck('id', 'name');
        $this->employees = Employee::pluck('id', 'name');
        $this->suppliers = Supplier::pluck('id', 'name');
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +1 for 0-index, +1 for heading row

            // 1. Validate the row data
            $validator = Validator::make($row->toArray(), [
                'asset_code' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'asset_category' => 'nullable|string',
                'asset_model' => 'nullable|string',
                'branch' => 'nullable|string',
                'location' => 'nullable|string',
                'department' => 'nullable|string',
                'employee' => 'nullable|string',
                'supplier' => 'nullable|string',
                'serial_number' => 'nullable|string|max:255',
                'barcode' => 'nullable|string|max:255',
                'purchase_date' => 'required|date',
                'purchase_cost' => 'required|numeric',
                'currency' => 'required|string|max:3',
                'warranty_end_date' => 'nullable|date',
                'status' => 'required|in:draft,active,maintenance,disposed,lost',
                'condition' => 'nullable|in:good,needs_repair,damaged',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                $this->recordValidationErrors($validator, $rowNumber);
                $this->skippedCount++;

                continue;
            }

            // 2. Resolve Foreign Keys
            $categoryId = $this->resolveLookupId($this->categories, $row['asset_category'], $rowNumber, 'asset_category', 'Category', false);
            if (! empty($row['asset_category']) && $categoryId === null) {
                $this->skippedCount++;

                continue;
            }

            $modelId = $this->resolveLookupId($this->models, $row['asset_model'], $rowNumber, 'asset_model', 'Model', false);
            if (! empty($row['asset_model']) && $modelId === null) {
                $this->skippedCount++;

                continue;
            }

            $branchId = $this->resolveLookupId($this->branches, $row['branch'], $rowNumber, 'branch', 'Branch', false);
            if (! empty($row['branch']) && $branchId === null) {
                $this->skippedCount++;

                continue;
            }

            $locationId = $this->resolveLookupId($this->locations, $row['location'], $rowNumber, 'location', 'Location', false);
            if (! empty($row['location']) && $locationId === null) {
                $this->skippedCount++;

                continue;
            }

            $departmentId = $this->resolveLookupId($this->departments, $row['department'], $rowNumber, 'department', 'Department', false);
            if (! empty($row['department']) && $departmentId === null) {
                $this->skippedCount++;

                continue;
            }

            $employeeId = $this->resolveLookupId($this->employees, $row['employee'], $rowNumber, 'employee', 'Employee', false);
            if (! empty($row['employee']) && $employeeId === null) {
                $this->skippedCount++;

                continue;
            }

            $supplierId = $this->resolveLookupId($this->suppliers, $row['supplier'], $rowNumber, 'supplier', 'Supplier', false);
            if (! empty($row['supplier']) && $supplierId === null) {
                $this->skippedCount++;

                continue;
            }

            // 3. Upsert Logic
            $this->performImportUpsert($rowNumber, function () use (
                $row,
                $categoryId,
                $modelId,
                $branchId,
                $locationId,
                $departmentId,
                $employeeId,
                $supplierId
            ): void {
                $rowData = is_array($row) ? $row : $row->toArray();

                Asset::updateOrCreate(
                    ['asset_code' => $rowData['asset_code']],
                    [
                        'name' => $rowData['name'],
                        'asset_category_id' => $categoryId,
                        'asset_model_id' => $modelId,
                        'branch_id' => $branchId,
                        'asset_location_id' => $locationId,
                        'department_id' => $departmentId,
                        'employee_id' => $employeeId,
                        'supplier_id' => $supplierId,
                        'serial_number' => $rowData['serial_number'] ?? null,
                        'barcode' => $rowData['barcode'] ?? null,
                        'purchase_date' => $rowData['purchase_date'],
                        'purchase_cost' => is_array($rowData['purchase_cost'])
                            ? reset($rowData['purchase_cost'])
                            : $rowData['purchase_cost'],
                        'currency' => strtoupper($rowData['currency']),
                        'warranty_end_date' => $rowData['warranty_end_date'] ?? null,
                        'status' => strtolower($rowData['status']),
                        'condition' => strtolower($rowData['condition']),
                        'notes' => $rowData['notes'] ?? null,
                    ]
                );
            }, true);
        }
    }
}
