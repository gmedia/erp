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
