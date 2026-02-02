<?php

use App\Actions\JournalEntries\ExportJournalEntriesAction;
use App\Http\Requests\JournalEntries\IndexJournalEntryRequest;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;

uses(RefreshDatabase::class)->group('journal-entries');

test('it returns export response', function () {
    JournalEntry::factory()->count(5)->create();

    $action = app(ExportJournalEntriesAction::class);
    $request = Mockery::mock(IndexJournalEntryRequest::class);
    $request->shouldReceive('validated')->andReturn([]);

    $response = $action->execute($request);

    expect($response)->toBeInstanceOf(JsonResponse::class);
    $data = $response->getData(true);
    
    expect($data)->toHaveKeys(['url', 'filename']);
    expect($data['filename'])->toContain('journal_entries_export');
});
