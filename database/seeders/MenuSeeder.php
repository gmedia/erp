<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                'name' => 'dashboard',
                'display_name' => 'Dashboard',
                'permissions' => [],
                'icon' => 'LayoutGrid',
                'url' => 'dashboard',
                'child' => [],
            ],
            [
                'name' => 'employee',
                'display_name' => 'Employee',
                'permissions' => ['employee', 'employee.create', 'employee.edit', 'employee.delete'],
                'icon' => 'UserCircle',
                'url' => 'employees',
                'child' => [],
            ],
            [
                'name' => 'customer',
                'display_name' => 'Customer',
                'permissions' => ['customer', 'customer.create', 'customer.edit', 'customer.delete'],
                'icon' => 'Users',
                'url' => 'customers',
                'child' => [],
            ],
            [
                'name' => 'supplier',
                'display_name' => 'Supplier',
                'permissions' => ['supplier', 'supplier.create', 'supplier.edit', 'supplier.delete'],
                'icon' => 'Truck',
                'url' => 'suppliers',
                'child' => [],
            ],
            [
                'name' => 'accounting',
                'display_name' => 'Accounting',
                'permissions' => ['journal_entry', 'posting_journal'],
                'icon' => 'Calculator',
                'url' => null,
                'child' => [
                    [
                        'name' => 'journal_entry',
                        'display_name' => 'Journal Entry',
                        'permissions' => ['journal_entry', 'journal_entry.create', 'journal_entry.edit', 'journal_entry.delete'],
                        'icon' => 'Book',
                        'url' => 'journal-entries',
                        'child' => [],
                    ],
                    [
                        'name' => 'posting_journal',
                        'display_name' => 'Posting Journal',
                        'permissions' => ['posting_journal'],
                        'icon' => 'CheckSquare',
                        'url' => 'posting-journals',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'asset',
                'display_name' => 'Asset',
                'permissions' => ['asset'],
                'icon' => 'Package',
                'url' => null,
                'child' => [
                    [
                        'name' => 'asset_data',
                        'display_name' => 'Asset Data',
                        'permissions' => ['asset', 'asset.create', 'asset.edit', 'asset.delete'],
                        'icon' => 'List',
                        'url' => 'assets',
                        'child' => [],
                    ],
                    [
                        'name' => 'asset_movement',
                        'display_name' => 'Asset Movement',
                        'permissions' => ['asset_movement'],
                        'icon' => 'ArrowLeftRight',
                        'url' => 'asset-movements',
                        'child' => [],
                    ],

                ],
            ],
            [
                'name' => 'report',
                'display_name' => 'Report',
                'permissions' => ['trial_balance_report', 'balance_sheet_report', 'income_statement_report', 'cash_flow_report', 'comparative_report'],
                'icon' => 'PieChart',
                'url' => null,
                'child' => [
                    [
                        'name' => 'trial_balance_report',
                        'display_name' => 'Trial Balance Report',
                        'permissions' => ['trial_balance_report'],
                        'icon' => 'Scale',
                        'url' => 'reports/trial-balance',
                        'child' => [],
                    ],
                    [
                        'name' => 'balance_sheet_report',
                        'display_name' => 'Balance Sheet Report',
                        'permissions' => ['balance_sheet_report'],
                        'icon' => 'Library',
                        'url' => 'reports/balance-sheet',
                        'child' => [],
                    ],
                    [
                        'name' => 'income_statement_report',
                        'display_name' => 'Income Statement Report',
                        'permissions' => ['income_statement_report'],
                        'icon' => 'TrendingUp',
                        'url' => 'reports/income-statement',
                        'child' => [],
                    ],
                    [
                        'name' => 'cash_flow_report',
                        'display_name' => 'Cash Flow Report',
                        'permissions' => ['cash_flow_report'],
                        'icon' => 'ArrowDownUp',
                        'url' => 'reports/cash-flow',
                        'child' => [],
                    ],
                    [
                        'name' => 'comparative_report',
                        'display_name' => 'Comparative Report',
                        'permissions' => ['comparative_report'],
                        'icon' => 'Copy',
                        'url' => 'reports/comparative',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'master.data',
                'display_name' => 'Master Data',
                'permissions' => ['department', 'position', 'branch', 'customer_category', 'supplier_category', 'product_category', 'unit', 'product', 'fiscal_year', 'coa_version', 'account', 'account_mapping', 'asset_category', 'asset_model', 'asset_location', 'accounts'],
                'icon' => 'Database',
                'url' => null,
                'child' => [
                    [
                        'name' => 'department',
                        'display_name' => 'Department',
                        'permissions' => ['department', 'department.create', 'department.edit', 'department.delete'],
                        'icon' => 'Building2',
                        'url' => 'departments',
                        'child' => [],
                    ],
                    [
                        'name' => 'position',
                        'display_name' => 'Position',
                        'permissions' => ['position', 'position.create', 'position.edit', 'position.delete'],
                        'icon' => 'Briefcase',
                        'url' => 'positions',
                        'child' => [],
                    ],
                    [
                        'name' => 'branch',
                        'display_name' => 'Branch',
                        'permissions' => ['branch', 'branch.create', 'branch.edit', 'branch.delete'],
                        'icon' => 'GitBranch',
                        'url' => 'branches',
                        'child' => [],
                    ],
                    [
                        'name' => 'customer_category',
                        'display_name' => 'Customer Category',
                        'permissions' => ['customer_category', 'customer_category.create', 'customer_category.edit', 'customer_category.delete'],
                        'icon' => 'Tags',
                        'url' => 'customer-categories',
                        'child' => [],
                    ],
                    [
                        'name' => 'supplier_category',
                        'display_name' => 'Supplier Category',
                        'permissions' => ['supplier_category', 'supplier_category.create', 'supplier_category.edit', 'supplier_category.delete'],
                        'icon' => 'Tags',
                        'url' => 'supplier-categories',
                        'child' => [],
                    ],
                    [
                        'name' => 'product_category',
                        'display_name' => 'Product Category',
                        'permissions' => ['product_category', 'product_category.create', 'product_category.edit', 'product_category.delete'],
                        'icon' => 'Box',
                        'url' => 'product-categories',
                        'child' => [],
                    ],
                    [
                        'name' => 'unit',
                        'display_name' => 'Unit',
                        'permissions' => ['unit', 'unit.create', 'unit.edit', 'unit.delete'],
                        'icon' => 'Ruler',
                        'url' => 'units',
                        'child' => [],
                    ],
                    [
                        'name' => 'product',
                        'display_name' => 'Product',
                        'permissions' => ['product', 'product.create', 'product.edit', 'product.delete'],
                        'icon' => 'ShoppingCart',
                        'url' => 'products',
                        'child' => [],
                    ],
                    [
                        'name' => 'fiscal_year',
                        'display_name' => 'Fiscal Year',
                        'permissions' => ['fiscal_year', 'fiscal_year.create', 'fiscal_year.edit', 'fiscal_year.delete'],
                        'icon' => 'Calendar',
                        'url' => 'fiscal-years',
                        'child' => [],
                    ],
                    [
                        'name' => 'coa_version',
                        'display_name' => 'COA Version',
                        'permissions' => ['coa_version', 'coa_version.create', 'coa_version.edit', 'coa_version.delete'],
                        'icon' => 'Link',
                        'url' => 'coa-versions',
                        'child' => [],
                    ],
                    [
                        'name' => 'chart_of_accounts',
                        'display_name' => 'Chart of Accounts',
                        'permissions' => ['account', 'account.create', 'account.edit', 'account.delete'],
                        'icon' => 'FolderTree',
                        'url' => 'accounts',
                        'child' => [],
                    ],
                    [
                        'name' => 'account_mapping',
                        'display_name' => 'Account Mappings',
                        'permissions' => ['account_mapping', 'account_mapping.create', 'account_mapping.edit', 'account_mapping.delete'],
                        'icon' => 'Layers',
                        'url' => 'account-mappings',
                        'child' => [],
                    ],
                    [
                        'name' => 'asset_category',
                        'display_name' => 'Asset Category',
                        'permissions' => ['asset_category', 'asset_category.create', 'asset_category.edit', 'asset_category.delete'],
                        'icon' => 'Tags',
                        'url' => 'asset-categories',
                        'child' => [],
                    ],
                    [
                        'name' => 'asset_model',
                        'display_name' => 'Asset Model',
                        'permissions' => ['asset_model', 'asset_model.create', 'asset_model.edit', 'asset_model.delete'],
                        'icon' => 'Box',
                        'url' => 'asset-models',
                        'child' => [],
                    ],
                    [
                        'name' => 'asset_location',
                        'display_name' => 'Asset Location',
                        'permissions' => ['asset_location', 'asset_location.create', 'asset_location.edit', 'asset_location.delete'],
                        'icon' => 'Map',
                        'url' => 'asset-locations',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'permissions' => ['permission', 'user'],
                'icon' => 'Shield',
                'url' => null,
                'child' => [
                    [
                        'name' => 'permission',
                        'display_name' => 'Permission',
                        'permissions' => ['permission'],
                        'icon' => 'Lock',
                        'url' => 'permissions',
                        'child' => [],
                    ],
                    [
                        'name' => 'user',
                        'display_name' => 'User',
                        'permissions' => ['user'],
                        'icon' => 'User',
                        'url' => 'users',
                        'child' => [],
                    ],
                ],
            ],
        ];

        $this->createMenus($menus);
    }

    private function createMenus(array $items, ?Menu $parent = null)
    {
        foreach ($items as $item) {            
            $newParent = Menu::updateOrCreate([
                'name' => $item['name'],
            ], [
                'display_name' => $item['display_name'],
                'icon' => $item['icon'],
                'url' => $item['url'],
                'parent_id' => $parent?->id,
            ]);

            foreach ($item['permissions'] as $permission) {
                $newParent->permissions()->attach(Permission::where('name', $permission)->first());
            }

            $this->createMenus($item['child'], $newParent);
        }
    }
}
