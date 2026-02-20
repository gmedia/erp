<?php

namespace App\Imports;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Branch;
use App\Models\AssetLocation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class AssetImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
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
                foreach ($validator->errors()->all() as $error) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'field' => 'Validation',
                        'message' => $error,
                    ];
                }
                $this->skippedCount++;
                continue;
            }

            // 2. Resolve Foreign Keys
            $categoryId = null;
            if (!empty($row['asset_category'])) {
                $categoryId = $this->categories->get($row['asset_category']);
                if (!$categoryId) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'field' => 'asset_category',
                        'message' => "Category '{$row['asset_category']}' not found.",
                    ];
                    $this->skippedCount++;
                    continue;
                }
            }

            $modelId = null;
            if (!empty($row['asset_model'])) {
                $modelId = $this->models->get($row['asset_model']);
                if (!$modelId) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'field' => 'asset_model',
                        'message' => "Model '{$row['asset_model']}' not found.",
                    ];
                    $this->skippedCount++;
                    continue;
                }
            }

            $branchId = null;
            if (!empty($row['branch'])) {
                $branchId = $this->branches->get($row['branch']);
                if (!$branchId) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'field' => 'branch',
                        'message' => "Branch '{$row['branch']}' not found.",
                    ];
                    $this->skippedCount++;
                    continue;
                }
            }

            $locationId = null;
            if (!empty($row['location'])) {
                $locationId = $this->locations->get($row['location']);
                if (!$locationId) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'field' => 'location',
                        'message' => "Location '{$row['location']}' not found.",
                    ];
                    $this->skippedCount++;
                    continue;
                }
            }

            $departmentId = null;
            if (!empty($row['department'])) {
                $departmentId = $this->departments->get($row['department']);
                if (!$departmentId) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'field' => 'department',
                        'message' => "Department '{$row['department']}' not found.",
                    ];
                    $this->skippedCount++;
                    continue;
                }
            }

            $employeeId = null;
            if (!empty($row['employee'])) {
                $employeeId = $this->employees->get($row['employee']);
                if (!$employeeId) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'field' => 'employee',
                        'message' => "Employee '{$row['employee']}' not found.",
                    ];
                    $this->skippedCount++;
                    continue;
                }
            }

            $supplierId = null;
            if (!empty($row['supplier'])) {
                $supplierId = $this->suppliers->get($row['supplier']);
                if (!$supplierId) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'field' => 'supplier',
                        'message' => "Supplier '{$row['supplier']}' not found.",
                    ];
                    $this->skippedCount++;
                    continue;
                }
            }

            // 3. Upsert Logic
            try {
                // Convert collection row to array
                $rowData = is_array($row) ? $row : $row->toArray();

                $asset = Asset::updateOrCreate(
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
                        'purchase_cost' => is_array($rowData['purchase_cost']) ? reset($rowData['purchase_cost']) : $rowData['purchase_cost'],
                        'currency' => strtoupper($rowData['currency']),
                        'warranty_end_date' => $rowData['warranty_end_date'] ?? null,
                        'status' => strtolower($rowData['status']),
                        'condition' => strtolower($rowData['condition']),
                        'notes' => $rowData['notes'] ?? null,
                    ]
                );

                if ($asset->wasRecentlyCreated) {
                    $this->importedCount++;
                } else {
                    $this->importedCount++; 
                }
            } catch (\Exception $e) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'field' => 'System',
                    'message' => "Failed to save: " . $e->getMessage(),
                ];
                $this->skippedCount++;
            }
        }
    }
}
