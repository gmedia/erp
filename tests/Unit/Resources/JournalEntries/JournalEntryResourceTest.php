<?php

use App\Http\Resources\JournalEntries\JournalEntryResource;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('journal-entries');

test('it transforms journal entry to array', function () {
    $journalEntry = JournalEntry::factory()->create();
    $resource = new JournalEntryResource($journalEntry);
    $data = $resource->resolve();

    expect($data)->toHaveKeys(['id', 'entry_number', 'entry_date', 'description', 'status']);
    expect($data['id'])->toBe($journalEntry->id);
    expect($data['entry_number'])->toBe($journalEntry->entry_number);
});
