<?php

namespace App\Imports;

use App\Http\Requests\Concerns\HasSupportedCurrencyRules;
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
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    use HasSupportedCurrencyRules;
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
        ['categories' => $this->categories,
            'models' => $this->models,
            'branches' => $this->branches,
            'locations' => $this->locations,
            'departments' => $this->departments,
            'employees' => $this->employees,
            'suppliers' => $this->suppliers] = $this->preloadLookupMaps([
                'categories' => ['model' => AssetCategory::class],
                'models' => ['model' => AssetModel::class, 'key' => 'model_name'],
                'branches' => ['model' => Branch::class],
                'locations' => ['model' => AssetLocation::class],
                'departments' => ['model' => Department::class],
                'employees' => ['model' => Employee::class],
                'suppliers' => ['model' => Supplier::class],
            ]);
    }

    public function collection(Collection $rows)
    {
        $this->processImportCollection(
            $rows,
            [
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
                'currency' => ['required', ...$this->supportedCurrencyRules()],
                'warranty_end_date' => 'nullable|date',
                'status' => 'required|in:draft,active,maintenance,disposed,lost',
                'condition' => 'nullable|in:good,needs_repair,damaged',
                'notes' => 'nullable|string',
            ],
            [
                $this->lookupConfig($this->categories, 'asset_category', 'Category', 'category_id'),
                $this->lookupConfig($this->models, 'asset_model', 'Model', 'model_id'),
                $this->lookupConfig($this->branches, 'branch', 'Branch', 'branch_id'),
                $this->lookupConfig($this->locations, 'location', 'Location', 'location_id'),
                $this->lookupConfig($this->departments, 'department', 'Department', 'department_id'),
                $this->lookupConfig($this->employees, 'employee', 'Employee', 'employee_id'),
                $this->lookupConfig($this->suppliers, 'supplier', 'Supplier', 'supplier_id'),
            ],
            function (array $rowData, array $resolvedLookups): void {
                Asset::updateOrCreate(
                    ['asset_code' => $rowData['asset_code']],
                    [
                        'name' => $rowData['name'],
                        'asset_category_id' => $resolvedLookups['category_id'],
                        'asset_model_id' => $resolvedLookups['model_id'],
                        'branch_id' => $resolvedLookups['branch_id'],
                        'asset_location_id' => $resolvedLookups['location_id'],
                        'department_id' => $resolvedLookups['department_id'],
                        'employee_id' => $resolvedLookups['employee_id'],
                        'supplier_id' => $resolvedLookups['supplier_id'],
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
            },
            true,
            true
        );
    }

    /**
     * Build a lookup config row for performImportFromRows.
     *
     * @return array<string, mixed>
     */
    private function lookupConfig($lookup, string $source, string $entity, string $target): array
    {
        return [
            'lookup' => $lookup,
            'source' => $source,
            'entity' => $entity,
            'target' => $target,
            'required' => false,
            'incrementSkippedOnFailure' => true,
        ];
    }
}
