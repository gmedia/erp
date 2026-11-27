<?php

use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('audit-logs');

beforeEach(function () {
    $this->admin = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $this->admin->id]);
    $employee->permissions()->sync([
        Permission::firstOrCreate(['name' => 'audit_log'], ['display_name' => 'Audit Log'])->id,
        Permission::firstOrCreate(['name' => 'audit_log.export'], ['display_name' => 'Export Audit Log'])->id,
    ]);
});

it('lists activities with attribute changes for an authorized user', function () {
    Activity::query()->create([
        'log_name' => 'finance',
        'description' => 'updated',
        'subject_type' => 'App\\Models\\JournalEntry',
        'subject_id' => 42,
        'causer_type' => User::class,
        'causer_id' => $this->admin->id,
        'event' => 'updated',
        'attribute_changes' => ['attributes' => ['status' => 'posted'], 'old' => ['status' => 'draft']],
    ]);

    Sanctum::actingAs($this->admin, ['*']);

    getJson('/api/audit-logs')
        ->assertOk()
        ->assertJsonPath('data.0.event', 'updated')
        ->assertJsonPath('data.0.subject_id', 42)
        ->assertJsonPath('data.0.attribute_changes.old.status', 'draft')
        ->assertJsonPath('data.0.attribute_changes.attributes.status', 'posted');
});

it('filters activities by event', function () {
    Activity::query()->create(['log_name' => 'finance', 'description' => 'created', 'event' => 'created']);
    Activity::query()->create(['log_name' => 'finance', 'description' => 'deleted', 'event' => 'deleted']);

    Sanctum::actingAs($this->admin, ['*']);

    getJson('/api/audit-logs?event=deleted')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.event', 'deleted');
});

it('rejects an event value outside created, updated and deleted', function () {
    Sanctum::actingAs($this->admin, ['*']);

    getJson('/api/audit-logs?event=login_success')->assertUnprocessable();
});

it('returns an export url for an authorized user', function () {
    Activity::query()->create(['log_name' => 'finance', 'description' => 'created', 'event' => 'created']);

    Sanctum::actingAs($this->admin, ['*']);

    postJson('/api/audit-logs/export', [])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);
});

it('forbids access without the audit_log permission', function () {
    $outsider = User::factory()->create();
    Sanctum::actingAs($outsider, ['*']);

    getJson('/api/audit-logs')->assertForbidden();
});
