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
    dump("Initial count: {$count}");
    expect($count)->toBeGreaterThan(0);
});

test('diagnostic: factory create then count', function () {
    $user = createTestUserWithPermissions(['employee']);
    Sanctum::actingAs($user, ['*']);

    $countBefore = Employee::count();
    dump("Count before factory create: {$countBefore}");

    $e = Employee::factory()->create();
    dump('Created employee ID: ' . $e->id);

    $countAfter = Employee::count();
    dump("Count after factory create: {$countAfter}");

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
    dump("DB raw count: {$dbCount}");

    $eloquentCount = Employee::count();
    dump("Eloquent count: {$eloquentCount}");

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
