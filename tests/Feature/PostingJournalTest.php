<?php

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('posting-journals');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['journal_entry.post']);
    actingAs($this->user);
    
    $this->fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31'
    ]);
});

test('it can list draft journal entries', function () {
    JournalEntry::factory()->count(3)->create([
        'status' => 'draft',
        'fiscal_year_id' => $this->fiscalYear->id
    ]);
    
    JournalEntry::factory()->create([
        'status' => 'posted',
        'fiscal_year_id' => $this->fiscalYear->id
    ]);

    $response = getJson('/api/posting-journals');

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

test('it can bulk post balanced journals', function () {
    $accounts = Account::factory()->count(2)->create();
    
    $entry1 = JournalEntry::factory()->create(['status' => 'draft', 'fiscal_year_id' => $this->fiscalYear->id]);
    JournalEntryLine::create(['journal_entry_id' => $entry1->id, 'account_id' => $accounts[0]->id, 'debit' => 1000, 'credit' => 0]);
    JournalEntryLine::create(['journal_entry_id' => $entry1->id, 'account_id' => $accounts[1]->id, 'debit' => 0, 'credit' => 1000]);

    $entry2 = JournalEntry::factory()->create(['status' => 'draft', 'fiscal_year_id' => $this->fiscalYear->id]);
    JournalEntryLine::create(['journal_entry_id' => $entry2->id, 'account_id' => $accounts[0]->id, 'debit' => 500, 'credit' => 0]);
    JournalEntryLine::create(['journal_entry_id' => $entry2->id, 'account_id' => $accounts[1]->id, 'debit' => 0, 'credit' => 500]);

    $response = postJson('/api/posting-journals/post', [
        'ids' => [$entry1->id, $entry2->id]
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success_count' => 2]);

    expect($entry1->fresh()->status)->toBe('posted')
        ->and($entry2->fresh()->status)->toBe('posted')
        ->and($entry1->fresh()->posted_at)->not->toBeNull()
        ->and($entry1->fresh()->posted_by)->toBe($this->user->id);
});

test('it fails to post unbalanced journals', function () {
    $account = Account::factory()->create();
    
    $entry = JournalEntry::factory()->create(['status' => 'draft', 'fiscal_year_id' => $this->fiscalYear->id]);
    JournalEntryLine::create(['journal_entry_id' => $entry->id, 'account_id' => $account->id, 'debit' => 1000, 'credit' => 0]);

    $response = postJson('/api/posting-journals/post', [
        'ids' => [$entry->id]
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success_count' => 0]);
    expect($response->json('failures'))->toHaveKey((string)$entry->id)
        ->and($entry->fresh()->status)->toBe('draft');
});
