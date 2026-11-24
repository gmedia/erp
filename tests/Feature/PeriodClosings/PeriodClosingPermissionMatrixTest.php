<?php

use App\Models\FiscalYear;
use App\Models\PeriodClosing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('period-closings');

test('period closing actions are forbidden without any period closing permission', function () {
    $periodClosing = PeriodClosing::factory()->create(['status' => 'draft']);
    Sanctum::actingAs(createTestUserWithPermissions([]), ['*']);

    getJson('/api/period-closings')->assertForbidden();
    postJson('/api/period-closings', [])->assertForbidden();
    postJson("/api/period-closings/{$periodClosing->id}/close")->assertForbidden();
    postJson("/api/period-closings/{$periodClosing->id}/reopen")->assertForbidden();
    postJson('/api/period-closings/export', [])->assertForbidden();
});

test('view-only period closing permission can export but cannot create or run actions', function () {
    Excel::fake();
    $periodClosing = PeriodClosing::factory()->create(['status' => 'draft']);
    Sanctum::actingAs(createTestUserWithPermissions(['period_closing']), ['*']);

    getJson('/api/period-closings')->assertOk();
    postJson('/api/period-closings', [])->assertForbidden();
    postJson("/api/period-closings/{$periodClosing->id}/close")->assertForbidden();
    postJson("/api/period-closings/{$periodClosing->id}/reopen")->assertForbidden();
    postJson('/api/period-closings/export', [])->assertOk();
});

test('create permission allows creating but not closing', function () {
    Excel::fake();
    $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);
    $periodClosing = PeriodClosing::factory()->create(['status' => 'draft']);
    Sanctum::actingAs(createTestUserWithPermissions(['period_closing', 'period_closing.create']), ['*']);

    postJson('/api/period-closings', [
        'fiscal_year_id' => $fiscalYear->id,
        'period_month' => 2,
        'period_year' => 2026,
        'closing_type' => 'monthly',
    ])->assertCreated();

    postJson("/api/period-closings/{$periodClosing->id}/close")->assertForbidden();
});

test('edit permission allows close, reopen and export', function () {
    Excel::fake();
    $closing = PeriodClosing::factory()->create(['status' => 'draft']);
    $closed = PeriodClosing::factory()->closed()->create();
    Sanctum::actingAs(createTestUserWithPermissions(['period_closing', 'period_closing.edit']), ['*']);

    postJson("/api/period-closings/{$closing->id}/close")->assertOk();
    postJson("/api/period-closings/{$closed->id}/reopen")->assertOk();
    postJson('/api/period-closings/export', [])->assertOk();
});
