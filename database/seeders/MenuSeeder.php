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
                'icon' => 'Users',
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
                'name' => 'master.data',
                'display_name' => 'Master Data',
                'permissions' => ['permission'],
                'icon' => 'LayoutList',
                'url' => null,
                'child' => [
                    [
                        'name' => 'department',
                        'display_name' => 'Department',
                        'permissions' => ['department', 'department.create', 'department.edit', 'department.delete'],
                        'icon' => 'LayoutList',
                        'url' => 'departments',
                        'child' => [],
                    ],
                    [
                        'name' => 'position',
                        'display_name' => 'Position',
                        'permissions' => ['position', 'position.create', 'position.edit', 'position.delete'],
                        'icon' => 'LayoutList',
                        'url' => 'positions',
                        'child' => [],
                    ],
                    [
                        'name' => 'branch',
                        'display_name' => 'Branch',
                        'permissions' => ['branch', 'branch.create', 'branch.edit', 'branch.delete'],
                        'icon' => 'LayoutList',
                        'url' => 'branches',
                        'child' => [],
                    ],
                ],
            ],
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'permissions' => ['permission'],
                'icon' => 'Settings2',
                'url' => null,
                'child' => [
                    [
                        'name' => 'permission',
                        'display_name' => 'Permission',
                        'permissions' => ['permission'],
                        'icon' => 'Settings2',
                        'url' => 'permissions',
                        'child' => [],
                    ],
                    [
                        'name' => 'user',
                        'display_name' => 'User',
                        'permissions' => ['user'],
                        'icon' => 'Settings2',
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
