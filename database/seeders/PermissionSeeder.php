<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'department',
                'display_name' => 'Department',
                'child' => [
                    [
                        'name' => 'department.create',
                        'display_name' => 'Create Department',
                        'child' => [],
                    ],
                    [
                        'name' => 'department.edit',
                        'display_name' => 'Edit Department',
                        'child' => [],
                    ],
                    [
                        'name' => 'department.delete',
                        'display_name' => 'Delete Department',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'position',
                'display_name' => 'Position',
                'child' => [
                    [
                        'name' => 'position.create',
                        'display_name' => 'Create Position',
                        'child' => [],
                    ],
                    [
                        'name' => 'position.edit',
                        'display_name' => 'Edit Position',
                        'child' => [],
                    ],
                    [
                        'name' => 'position.delete',
                        'display_name' => 'Delete Position',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'branch',
                'display_name' => 'Branch',
                'child' => [
                    [
                        'name' => 'branch.create',
                        'display_name' => 'Create Branch',
                        'child' => [],
                    ],
                    [
                        'name' => 'branch.edit',
                        'display_name' => 'Edit Branch',
                        'child' => [],
                    ],
                    [
                        'name' => 'branch.delete',
                        'display_name' => 'Delete Branch',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'employee',
                'display_name' => 'Employee',
                'child' => [
                    [
                        'name' => 'employee.create',
                        'display_name' => 'Create Employee',
                        'child' => [],
                    ],
                    [
                        'name' => 'employee.edit',
                        'display_name' => 'Edit Employee',
                        'child' => [],
                    ],
                    [
                        'name' => 'employee.delete',
                        'display_name' => 'Delete Employee',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'customer',
                'display_name' => 'Customer',
                'child' => [
                    [
                        'name' => 'customer.create',
                        'display_name' => 'Create Customer',
                        'child' => [],
                    ],
                    [
                        'name' => 'customer.edit',
                        'display_name' => 'Edit Customer',
                        'child' => [],
                    ],
                    [
                        'name' => 'customer.delete',
                        'display_name' => 'Delete Customer',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'supplier',
                'display_name' => 'Supplier',
                'child' => [
                    [
                        'name' => 'supplier.create',
                        'display_name' => 'Create Supplier',
                        'child' => [],
                    ],
                    [
                        'name' => 'supplier.edit',
                        'display_name' => 'Edit Supplier',
                        'child' => [],
                    ],
                    [
                        'name' => 'supplier.delete',
                        'display_name' => 'Delete Supplier',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'journal_entry',
                'display_name' => 'Journal Entry',
                'child' => [
                    [
                        'name' => 'journal_entry.create',
                        'display_name' => 'Create Journal Entry',
                        'child' => [],
                    ],
                    [
                        'name' => 'journal_entry.edit',
                        'display_name' => 'Edit Journal Entry',
                        'child' => [],
                    ],
                    [
                        'name' => 'journal_entry.delete',
                        'display_name' => 'Delete Journal Entry',
                        'child' => [],
                    ],
                    [
                        'name' => 'journal_entry.post',
                        'display_name' => 'Post Journal Entry',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'posting_journal',
                'display_name' => 'Posting Journal',
                'child' => [],
            ],
            [
                'name' => 'trial_balance_report',
                'display_name' => 'Trial Balance Report',
                'child' => [],
            ],
            [
                'name' => 'balance_sheet_report',
                'display_name' => 'Balance Sheet Report',
                'child' => [],
            ],
            [
                'name' => 'income_statement_report',
                'display_name' => 'Income Statement Report',
                'child' => [],
            ],
            [
                'name' => 'cash_flow_report',
                'display_name' => 'Cash Flow Report',
                'child' => [],
            ],
            [
                'name' => 'comparative_report',
                'display_name' => 'Comparative Report',
                'child' => [],
            ],
            [
                'name' => 'supplier_category',
                'display_name' => 'Supplier Category',
                'child' => [
                    [
                        'name' => 'supplier_category.create',
                        'display_name' => 'Create Supplier Category',
                        'child' => [],
                    ],
                    [
                        'name' => 'supplier_category.edit',
                        'display_name' => 'Edit Supplier Category',
                        'child' => [],
                    ],
                    [
                        'name' => 'supplier_category.delete',
                        'display_name' => 'Delete Supplier Category',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'customer_category',
                'display_name' => 'Customer Category',
                'child' => [
                    [
                        'name' => 'customer_category.create',
                        'display_name' => 'Create Customer Category',
                        'child' => [],
                    ],
                    [
                        'name' => 'customer_category.edit',
                        'display_name' => 'Edit Customer Category',
                        'child' => [],
                    ],
                    [
                        'name' => 'customer_category.delete',
                        'display_name' => 'Delete Customer Category',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'product_category',
                'display_name' => 'Product Category',
                'child' => [
                    [
                        'name' => 'product_category.create',
                        'display_name' => 'Create Product Category',
                        'child' => [],
                    ],
                    [
                        'name' => 'product_category.edit',
                        'display_name' => 'Edit Product Category',
                        'child' => [],
                    ],
                    [
                        'name' => 'product_category.delete',
                        'display_name' => 'Delete Product Category',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'unit',
                'display_name' => 'Unit',
                'child' => [
                    [
                        'name' => 'unit.create',
                        'display_name' => 'Create Unit',
                        'child' => [],
                    ],
                    [
                        'name' => 'unit.edit',
                        'display_name' => 'Edit Unit',
                        'child' => [],
                    ],
                    [
                        'name' => 'unit.delete',
                        'display_name' => 'Delete Unit',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'product',
                'display_name' => 'Product',
                'child' => [
                    [
                        'name' => 'product.create',
                        'display_name' => 'Create Product',
                        'child' => [],
                    ],
                    [
                        'name' => 'product.edit',
                        'display_name' => 'Edit Product',
                        'child' => [],
                    ],
                    [
                        'name' => 'product.delete',
                        'display_name' => 'Delete Product',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'fiscal_year',
                'display_name' => 'Fiscal Year',
                'child' => [
                    [
                        'name' => 'fiscal_year.create',
                        'display_name' => 'Create Fiscal Year',
                        'child' => [],
                    ],
                    [
                        'name' => 'fiscal_year.edit',
                        'display_name' => 'Edit Fiscal Year',
                        'child' => [],
                    ],
                    [
                        'name' => 'fiscal_year.delete',
                        'display_name' => 'Delete Fiscal Year',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'coa_version',
                'display_name' => 'COA Version',
                'child' => [
                    [
                        'name' => 'coa_version.create',
                        'display_name' => 'Create COA Version',
                        'child' => [],
                    ],
                    [
                        'name' => 'coa_version.edit',
                        'display_name' => 'Edit COA Version',
                        'child' => [],
                    ],
                    [
                        'name' => 'coa_version.delete',
                        'display_name' => 'Delete COA Version',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'account',
                'display_name' => 'Chart of Accounts',
                'child' => [
                    [
                        'name' => 'account.create',
                        'display_name' => 'Create Chart of Accounts',
                        'child' => [],
                    ],
                    [
                        'name' => 'account.edit',
                        'display_name' => 'Edit Chart of Accounts',
                        'child' => [],
                    ],
                    [
                        'name' => 'account.delete',
                        'display_name' => 'Delete Chart of Accounts',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'permission',
                'display_name' => 'Permission',
                'child' => [],
            ],
            [
                'name' => 'user',
                'display_name' => 'User',
                'child' => [],
            ],
        ];

        $permissions = $this->createPermissions($permissions);
        
        $admin = Employee::where('email', 'admin@admin.com')->first();
        $admin->permissions()->sync($permissions);
    }

    private function createPermissions(array $items, ?Permission $parent = null): array
    {
        $permissions = [];

        foreach ($items as $item) {
            $newParent = Permission::updateOrCreate([
                'name' => $item['name'],
            ], [
                'display_name' => $item['display_name'],
                'parent_id' => $parent?->id,
            ]);

            array_push($permissions, $newParent->id);
            $permissions = array_merge($permissions, $this->createPermissions($item['child'], $newParent));
        }

        return $permissions;
    }
}
