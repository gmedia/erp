<?php

use App\Models\Employee;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

test('diagnostic: just count employees after beforeEach', function () {
    $user = createTestUserWithPermissions(['employee']);
    Sanctum::actingAs($user, ['*']);

    $count = Employee::count();
    expect($count)->toBeGreaterThan(0);
});

test('diagnostic: factory create then count', function () {
    $user = createTestUserWithPermissions(['employee']);
    Sanctum::actingAs($user, ['*']);

    $countBefore = Employee::count();

    $e = Employee::factory()->create();

    $countAfter = Employee::count();

    expect($countAfter)->toBeGreaterThan(0);
});

test('diagnostic: raw DB insert then count', function () {
    $user = createTestUserWithPermissions(['employee']);
    Sanctum::actingAs($user, ['*']);

    DB::table('employees')->insert([
        'name' => 'Raw Test',
        'email' => 'raw@example.com',
        'user_id' => null,
    ]);

    $dbCount = DB::table('employees')->count();

    $eloquentCount = Employee::count();

    expect($dbCount)->toBeGreaterThan(0);
    expect($eloquentCount)->toEqual($dbCount);
});

test('diagnostic: fresh model instance count', function () {
    $user = createTestUserWithPermissions(['employee']);
    Sanctum::actingAs($user, ['*']);

    $app = app();
    $freshModel = $app->make(Employee::class);
    $count = $freshModel->newQuery()->count();
    dump("Fresh query count: {$count}");
    expect($count)->toBeGreaterThan(0);
});
