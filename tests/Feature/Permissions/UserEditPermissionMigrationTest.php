<?php

use App\Models\Employee;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class)->group('route-permission-scan');

/**
 * Jalankan migration izin tanpa refresh database penuh.
 * Migration ini hanya menambah baris dan memberi user.edit saat terkunci total.
 */
function runPermissionCatalogMigration(): void
{
    $migration = require database_path(
        'migrations/2026_11_24_090000_add_edit_permissions_for_period_closing_and_user.php',
    );
    $migration->up();
}

/**
 * @return array{permissions: list<string>, pivot: list<list<int>>}
 */
function snapshotPermissionState(): array
{
    $pivot = DB::table('employee_permission')
        ->orderBy('employee_id')
        ->orderBy('permission_id')
        ->get(['employee_id', 'permission_id'])
        ->map(fn ($row) => [(int) $row->employee_id, (int) $row->permission_id])
        ->values()
        ->all();

    return [
        'permissions' => Permission::query()->orderBy('name')->pluck('name')->all(),
        'pivot' => $pivot,
    ];
}

/**
 * Simulasikan instalasi lama tanpa user.edit: hapus baris permission dan pivotnya.
 */
function removeUserEditPermission(): void
{
    $userEditId = Permission::query()->where('name', 'user.edit')->value('id');

    DB::table('employee_permission')->where('permission_id', $userEditId)->delete();
    Permission::query()->where('name', 'user.edit')->delete();
}

function grantBaseToEmployee(Employee $employee, array $names): void
{
    $ids = Permission::query()->whereIn('name', $names)->pluck('id');
    $employee->permissions()->syncWithoutDetaching($ids->all());
}

it('memberi user.edit kepada karyawan yang memegang semua izin lama', function () {
    $base = Permission::query()->where('name', '!=', 'user.edit')->pluck('name')->all();
    $fullHolder = Employee::factory()->create();
    $partialHolder = Employee::factory()->create();

    grantBaseToEmployee($fullHolder, $base);
    grantBaseToEmployee($partialHolder, array_slice($base, 0, -1));

    removeUserEditPermission();

    runPermissionCatalogMigration();

    $userEditId = Permission::where('name', 'user.edit')->value('id');

    expect($fullHolder->permissions()->where('permissions.id', $userEditId)->exists())->toBeTrue();
    expect($partialHolder->permissions()->where('permissions.id', $userEditId)->exists())->toBeFalse();
});

it('tidak memberi siapa pun user.edit bila tidak ada pemegang semua izin lama', function () {
    $base = Permission::query()->where('name', '!=', 'user.edit')->pluck('name')->all();
    $partialHolder = Employee::factory()->create();

    grantBaseToEmployee($partialHolder, array_slice($base, 0, -1));

    removeUserEditPermission();

    runPermissionCatalogMigration();

    $userEditId = Permission::where('name', 'user.edit')->value('id');

    expect(DB::table('employee_permission')->where('permission_id', $userEditId)->count())->toBe(0);
});

it('tidak mengubah izin user.edit yang sudah dipegang karyawan', function () {
    $base = Permission::query()->where('name', '!=', 'user.edit')->pluck('name')->all();
    $existingHolder = Employee::factory()->create();
    $fullHolder = Employee::factory()->create();

    grantBaseToEmployee($fullHolder, $base);
    $userEditId = Permission::where('name', 'user.edit')->value('id');
    $existingHolder->permissions()->syncWithoutDetaching([$userEditId]);

    $beforeCount = DB::table('employee_permission')->where('permission_id', $userEditId)->count();

    runPermissionCatalogMigration();

    expect(DB::table('employee_permission')->where('permission_id', $userEditId)->count())->toBe($beforeCount);
    expect($fullHolder->permissions()->where('permissions.id', $userEditId)->exists())->toBeFalse();
});

it('menghasilkan hasil yang sama bila dijalankan dua kali', function () {
    $base = Permission::query()->where('name', '!=', 'user.edit')->pluck('name')->all();
    $fullHolder = Employee::factory()->create();
    grantBaseToEmployee($fullHolder, $base);

    removeUserEditPermission();

    runPermissionCatalogMigration();
    $afterFirst = snapshotPermissionState();

    runPermissionCatalogMigration();
    $afterSecond = snapshotPermissionState();

    expect($afterSecond)->toBe($afterFirst);
});
