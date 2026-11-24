<?php

use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('journal-entries');

test('journal entry import route is not registered', function () {
    $registered = collect(Route::getRoutes()->getRoutes())
        ->contains(fn ($route) => $route->uri() === 'api/journal-entries/import');

    expect($registered)->toBeFalse();
});

test('posting to journal entry import is rejected without creating entries', function () {
    $user = createTestUserWithPermissions([
        'journal_entry',
        'journal_entry.create',
    ]);
    Sanctum::actingAs($user, ['*']);

    // Path cocok dengan route {journal_entry} (PUT/DELETE), jadi POST ditolak 405, bukan 500.
    postJson('/api/journal-entries/import', [])
        ->assertStatus(405);

    expect(JournalEntry::count())->toBe(0);
});
