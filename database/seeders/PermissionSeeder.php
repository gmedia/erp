<?php

namespace Database\Seeders;

use App\Models\Permission;
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
        ];

        $this->createPermissions($permissions);
    }

    private function createPermissions(array $items, ?Permission $parent = null)
    {
        foreach ($items as $item) {
            $newParent = Permission::firstOrCreate([
                'name' => $item['name'],
                'display_name' => $item['display_name'],
                'parent_id' => $parent?->id,
            ]);

            $this->createPermissions($item['child'], $newParent);
        }
    }
}
