<?php

use App\Actions\JournalEntries\IndexJournalEntriesAction;
use App\Http\Requests\JournalEntries\IndexJournalEntryRequest;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('journal-entries');

test('it returns paginated journal entries', function () {
    JournalEntry::factory()->count(15)->create();

    $action = app(IndexJournalEntriesAction::class);
    $request = new IndexJournalEntryRequest(['per_page' => 10]);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
    expect($result->total())->toBe(15);
    expect($result->perPage())->toBe(10);
});

test('it applies filters via service', function () {
    JournalEntry::factory()->create(['status' => 'posted']);
    JournalEntry::factory()->create(['status' => 'draft']);

    $action = app(IndexJournalEntriesAction::class);
    $request = new IndexJournalEntryRequest(['status' => 'posted']);

    $result = $action->execute($request);

    expect($result->total())->toBe(1);
    expect($result->first()->status)->toBe('posted');
});
