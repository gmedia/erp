<?php

use App\Models\CreditNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('credit-notes');

test('it can export credit notes', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-04 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    $user = createTestUserWithPermissions(['credit_note']);
    Sanctum::actingAs($user, ['*']);

    CreditNote::factory()->count(5)->create();

    $response = postJson('/api/credit-notes/export')
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('credit_notes_export_2026-03-04_10-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
