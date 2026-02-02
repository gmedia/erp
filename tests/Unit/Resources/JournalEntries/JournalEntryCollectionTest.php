<?php

use App\Http\Resources\JournalEntries\JournalEntryCollection;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('journal-entries');

test('it transforms collection of journal entries', function () {
    $journalEntries = JournalEntry::factory()->count(3)->create();
    $collection = new JournalEntryCollection($journalEntries);
    $data = $collection->response()->getData(true);

    expect($data['data'])->toHaveCount(3);
    expect($data['data'][0])->toHaveKeys(['id', 'entry_number', 'entry_date']);
});
