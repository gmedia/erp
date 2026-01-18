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
                'child' => [
                    [
                        'name' => 'department.create',
                        'child' => [],
                    ],
                    [
                        'name' => 'department.edit',
                        'child' => [],
                    ],
                    [
                        'name' => 'department.delete',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'position',
                'child' => [
                    [
                        'name' => 'position.create',
                        'child' => [],
                    ],
                    [
                        'name' => 'position.edit',
                        'child' => [],
                    ],
                    [
                        'name' => 'position.delete',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'employee',
                'child' => [
                    [
                        'name' => 'employee.create',
                        'child' => [],
                    ],
                    [
                        'name' => 'employee.edit',
                        'child' => [],
                    ],
                    [
                        'name' => 'employee.delete',
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
                'parent_id' => $parent?->id,
            ]);

            $this->createPermissions($item['child'], $newParent);
        }
    }
}
