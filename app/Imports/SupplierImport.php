<?php

namespace App\Imports;

use App\Imports\Concerns\InteractsWithImportRows;
use App\Models\Branch;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SupplierImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    use InteractsWithImportRows;

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
            if (! $this->validateImportRow($row, $rowNumber, [
                'name' => 'required|string|max:255',
                'email' => 'nullable|email',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'branch' => 'nullable|string',
                'category' => 'required|string',
                'status' => 'required|in:active,inactive',
            ])) {

                continue;
            }

            // 2. Resolve Foreign Keys
            $categoryId = $this->resolveLookupId($this->categories, $row['category'], $rowNumber, 'category', 'Category');
            if ($categoryId === null) {
                continue;
            }

            $branchId = $this->resolveLookupId($this->branches, $row['branch'], $rowNumber, 'branch', 'Branch', false);
            if (! empty($row['branch']) && $branchId === null) {
                continue;
            }

            // 3. Upsert Logic
            $this->performImportUpsert($rowNumber, function () use ($row, $categoryId, $branchId): void {
                $matchAttributes = [];

                if (! empty($row['email'])) {
                    $matchAttributes['email'] = $row['email'];
                } else {
                    $matchAttributes['name'] = $row['name'];
                }

                Supplier::updateOrCreate(
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
            });
        }
    }
}
