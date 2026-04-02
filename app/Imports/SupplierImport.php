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
            $rowData = $this->rowToArray($row);

            // 1. Validate the row data
            if (! $this->validateImportRow($rowData, $rowNumber, [
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
            $resolvedLookups = $this->resolveLookupAssignments($rowData, $rowNumber, [
                ['lookup' => $this->categories, 'source' => 'category', 'entity' => 'Category', 'target' => 'category_id'],
                ['lookup' => $this->branches, 'source' => 'branch', 'entity' => 'Branch', 'target' => 'branch_id', 'required' => false],
            ]);

            if ($resolvedLookups === null) {
                continue;
            }

            // 3. Upsert Logic
            $this->performImportUpsert($rowNumber, function () use ($rowData, $resolvedLookups): void {
                $matchAttributes = [];

                if (! empty($rowData['email'])) {
                    $matchAttributes['email'] = $rowData['email'];
                } else {
                    $matchAttributes['name'] = $rowData['name'];
                }

                Supplier::updateOrCreate(
                    $matchAttributes,
                    [
                        'name' => $rowData['name'],
                        'email' => $rowData['email'] ?? null,
                        'phone' => $rowData['phone'] ?? null,
                        'address' => $rowData['address'] ?? null,
                        'category_id' => $resolvedLookups['category_id'],
                        'branch_id' => $resolvedLookups['branch_id'],
                        'status' => $rowData['status'],
                    ]
                );
            });
        }
    }
}
