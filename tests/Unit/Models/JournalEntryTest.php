<?php

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('journal-entries');

test('factory creates a valid journal entry', function () {
    $journalEntry = JournalEntry::factory()->create();

    $this->assertDatabaseHas('journal_entries', ['id' => $journalEntry->id]);

    expect($journalEntry->entry_number)->toBeString();
    expect($journalEntry->entry_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('it calculates total debit and credit correctly', function () {
    $journalEntry = JournalEntry::factory()->create();
    
    // Debit line
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'debit' => 1000,
        'credit' => 0
    ]);

    // Credit line
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'debit' => 0,
        'credit' => 1000
    ]);

    expect($journalEntry->total_debit)->toBe(1000.0);
    expect($journalEntry->total_credit)->toBe(1000.0);
    expect($journalEntry->isBalanced())->toBeTrue();
});

test('it identifies unbalanced entry', function () {
    $journalEntry = JournalEntry::factory()->create();
    
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $journalEntry->id,
        'debit' => 1000,
        'credit' => 0
    ]);

    expect($journalEntry->isBalanced())->toBeFalse();
});
