<?php

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('journal-entries');

describe('Journal Entry API Endpoints', function () {
    beforeEach(function () {
        // Create user with all journal entry permissions
        $user = createTestUserWithPermissions(['journal_entry', 'journal_entry.create', 'journal_entry.edit', 'journal_entry.delete']);
        actingAs($user);
    });

    test('index returns paginated journal entries', function () {
        JournalEntry::factory()->count(15)->create();

        $response = getJson('/api/journal-entries?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'entry_number',
                        'entry_date',
                        'reference',
                        'description',
                        'status',
                        'total_debit',
                        'total_credit',
                        'created_by' => ['id', 'name'],
                    ]
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);

        expect($response->json('meta.total'))->toBe(15)
            ->and($response->json('meta.per_page'))->toBe(10)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('store creates journal entry with lines', function () {
        $fiscalYear = FiscalYear::factory()->create(['status' => 'open', 'start_date' => '2023-01-01', 'end_date' => '2023-12-31']);
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $data = [
            'entry_date' => '2023-01-15',
            'description' => 'Opening Balance',
            'reference' => 'REF-001',
            'lines' => [
                [
                    'account_id' => $account1->id,
                    'debit' => 1000,
                    'credit' => 0,
                    'memo' => 'Debit Line'
                ],
                [
                    'account_id' => $account2->id,
                    'debit' => 0,
                    'credit' => 1000,
                    'memo' => 'Credit Line'
                ],
            ],
        ];

        $response = postJson('/api/journal-entries', $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'entry_number', 'lines']
            ]);
        
        $entry = JournalEntry::first();
        expect($entry->description)->toBe('Opening Balance');
        expect($entry->lines)->toHaveCount(2);

        assertDatabaseHas('journal_entries', ['description' => 'Opening Balance']);
        assertDatabaseHas('journal_entry_lines', ['account_id' => $account1->id, 'debit' => 1000]);
    });

    test('update modifies journal entry', function () {
        $fiscalYear = FiscalYear::factory()->create(['status' => 'open', 'start_date' => '2023-01-01', 'end_date' => '2023-12-31']);
        $journalEntry = JournalEntry::factory()->create(['entry_date' => '2023-01-10', 'fiscal_year_id' => $fiscalYear->id]);
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $data = [
            'entry_date' => '2023-01-20',
            'description' => 'Updated Description',
            'lines' => [
                [
                    'account_id' => $account1->id,
                    'debit' => 500,
                    'credit' => 0,
                ],
                [
                    'account_id' => $account2->id,
                    'debit' => 0,
                    'credit' => 500,
                ],
            ],
        ];

        $response = putJson("/api/journal-entries/{$journalEntry->id}", $data);

        $response->assertOk();
        
        $journalEntry->refresh();
        expect($journalEntry->description)->toBe('Updated Description');
        expect($journalEntry->total_debit)->toBe(500.0);
    });

    test('destroy deletes journal entry', function () {
        $journalEntry = JournalEntry::factory()->create(['status' => 'draft']);
        
        $response = deleteJson("/api/journal-entries/{$journalEntry->id}");
        
        $response->assertNoContent();
        
        assertDatabaseMissing('journal_entries', ['id' => $journalEntry->id]);
    });

    test('cannot delete posted journal entry', function () {
        $journalEntry = JournalEntry::factory()->create(['status' => 'posted']);
        
        $response = deleteJson("/api/journal-entries/{$journalEntry->id}");
        
        $response->assertStatus(403);
        
        assertDatabaseHas('journal_entries', ['id' => $journalEntry->id]);
    });
});
