<?php

use App\Models\RecurringJournal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('recurring-journals');

test('it exports recurring journals to xlsx', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-11 10:00:00'));
    Excel::fake();
    Storage::fake('public');
    Sanctum::actingAs(createTestUserWithPermissions(['recurring_journal']), ['*']);
    RecurringJournal::factory()->count(2)->create();

    $response = postJson('/api/recurring-journals/export', [])->assertOk()->assertJsonStructure(['url', 'filename']);

    expect($response->json('filename'))->toEndWith('.xlsx');
    Carbon::setTestNow();
});
