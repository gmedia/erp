<?php

use App\Models\Permission;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class)->group('route-permission-scan');

/**
 * Kumpulkan seluruh nama permission yang dipakai middleware `permission:` di routes/.
 * Argumen flag `true` (mis. `permission:period_closing,true`) bukan nama permission.
 *
 * @return list<string>
 */
function permissionNamesUsedByRoutes(): array
{
    $names = [];

    foreach (File::allFiles(base_path('routes')) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        preg_match_all('/permission:([A-Za-z0-9_.,-]+)/', $file->getContents(), $matches);

        foreach ($matches[1] as $group) {
            foreach (explode(',', $group) as $name) {
                if ($name !== 'true') {
                    $names[$name] = true;
                }
            }
        }
    }

    $sorted = array_keys($names);
    sort($sorted);

    return $sorted;
}

/**
 * @return list<string>
 */
function permissionNamesInTable(): array
{
    $names = DB::table('permissions')->pluck('name')->all();
    sort($names);

    return $names;
}

it('memindai minimal satu permission dari routes', function () {
    expect(permissionNamesUsedByRoutes())->not->toBeEmpty();
});

it('setiap permission route tersedia di tabel setelah migrate saja', function () {
    $missing = array_values(array_diff(permissionNamesUsedByRoutes(), permissionNamesInTable()));

    expect($missing)->toBe([]);
});

it('setiap permission route tersedia di tabel setelah seed', function () {
    Artisan::call('db:seed', ['--class' => PermissionSeeder::class, '--force' => true]);

    $missing = array_values(array_diff(permissionNamesUsedByRoutes(), permissionNamesInTable()));

    expect($missing)->toBe([]);
});

it('permission route yang ditambahkan migration memiliki parent dan display_name', function () {
    $names = permissionNamesUsedByRoutes();

    foreach ($names as $name) {
        $permission = Permission::query()->where('name', $name)->first();

        expect($permission)->not->toBeNull("permission {$name} tidak ada");
        expect($permission->display_name)->not->toBeEmpty("display_name {$name} kosong");

        if (str_contains($name, '.')) {
            $parent = explode('.', $name)[0];
            expect($permission->parent?->name)->toBe($parent, "parent {$name} keliru");
        }
    }
});
