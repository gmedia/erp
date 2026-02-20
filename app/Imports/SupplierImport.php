<?php

namespace App\Imports;

use App\Models\Branch;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class SupplierImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];

    protected $categories;
    protected $branches;

    public function __construct()
    {
        // Pre-load all potential lookups to minimize queries
        $this->categories = SupplierCategory::pluck('id', 'name'); // name => id
        $this->branches = Branch::pluck('id', 'name');             // name => id
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +1 for 0-index, +1 for heading row

            // 1. Validate the row data
            $validator = Validator::make($row->toArray(), [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'branch' => 'nullable|string',
                'category' => 'required|string',
                'status' => 'required|in:active,inactive',
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
            $categoryId = $this->categories->get($row['category']);
            if (!$categoryId) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'field' => 'category',
                    'message' => "Category '{$row['category']}' not found.",
                ];
                continue;
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
                    continue;
                }
            }

            // 3. Upsert Logic
            try {
                // If email is provided, we use it for matching (since it must be unique). Else we match by name.
                $matchAttributes = [];
                if (!empty($row['email'])) {
                    $matchAttributes['email'] = $row['email'];
                } else {
                    $matchAttributes['name'] = $row['name'];
                }

                $supplier = Supplier::updateOrCreate(
                    $matchAttributes,
                    [
                        'name' => $row['name'],
                        'email' => $row['email'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'address' => $row['address'] ?? null,
                        'category_id' => $categoryId,
                        'branch_id' => $branchId,
                        'status' => $row['status'],
                    ]
                );

                if ($supplier->wasRecentlyCreated) {
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
            }
        }
    }
}
