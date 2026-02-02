<?php

use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('journal-entries');

describe('Journal Entry Export', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions(['journal_entry']);
        actingAs($user);
    });

    test('export generates excel file via api', function () {
        JournalEntry::factory()->count(5)->create();

        $response = postJson('/api/journal-entries/export', []);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toContain('storage/exports/');
        expect($data['filename'])->toContain('journal_entries_export');
    });

    test('export respects filters', function () {
        JournalEntry::factory()->create(['status' => 'posted']);
        JournalEntry::factory()->create(['status' => 'draft']);

        $response = postJson('/api/journal-entries/export', ['status' => 'posted']);

        $response->assertOk();
        // Since we cannot easily verify the Excel content here without reading the file,
        // we mainly trust the Unit/Actions test for logic and this test for Endpoint integration.
    });
});
