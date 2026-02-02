<?php

use App\Domain\JournalEntries\JournalEntryFilterService;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('journal-entries');

test('it filters by search term', function () {
    JournalEntry::factory()->create(['entry_number' => 'JV-001', 'description' => 'Test Entry']);
    JournalEntry::factory()->create(['entry_number' => 'JV-002', 'description' => 'Another Entry']);

    $service = new JournalEntryFilterService();
    $query = JournalEntry::query();
    
    $service->applySearch($query, 'JV-001', ['entry_number', 'description']);
    expect($query->count())->toBe(1);
    
    $query = JournalEntry::query();
    $service->applySearch($query, 'Test', ['entry_number', 'description']);
    expect($query->count())->toBe(1);
});

test('it filters by status', function () {
    JournalEntry::factory()->create(['status' => 'draft']);
    JournalEntry::factory()->create(['status' => 'posted']);

    $service = new JournalEntryFilterService();
    $query = JournalEntry::query();
    
    $filters = ['status' => 'draft'];
    $service->applyAdvancedFilters($query, $filters);
    
    expect($query->count())->toBe(1);
    expect($query->first()->status)->toBe('draft');
});

test('it filters by date range', function () {
    JournalEntry::factory()->create(['entry_date' => '2023-01-15']);
    JournalEntry::factory()->create(['entry_date' => '2023-02-15']);

    $service = new JournalEntryFilterService();
    $query = JournalEntry::query();
    
    $filters = ['start_date' => '2023-01-01', 'end_date' => '2023-01-31'];
    $service->applyAdvancedFilters($query, $filters);
    
    expect($query->count())->toBe(1);
    expect($query->first()->entry_date->format('Y-m-d'))->toBe('2023-01-15');
});
