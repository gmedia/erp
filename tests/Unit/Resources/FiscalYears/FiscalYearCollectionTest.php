<?php

use App\Http\Resources\FiscalYears\FiscalYearCollection;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('fiscal-years');

test('collection returns multiple resources', function () {
    FiscalYear::factory()->count(3)->create();

    $collection = new FiscalYearCollection(FiscalYear::all());
    $data = $collection->toArray(request());

    expect($data)->toHaveCount(3);
});

test('collection includes preferred_fiscal_year_id in meta', function () {
    $fyWithEntries = FiscalYear::factory()->create(['status' => 'open', 'start_date' => '2025-01-01']);
    FiscalYear::factory()->create(['status' => 'open', 'start_date' => '2026-01-01']);

    JournalEntry::factory()->create([
        'fiscal_year_id' => $fyWithEntries->id,
        'status' => 'posted',
    ]);

    $paginated = FiscalYear::orderBy('start_date', 'desc')->paginate(15);
    $response = (new FiscalYearCollection($paginated))->response()->getData(true);

    expect($response['meta']['preferred_fiscal_year_id'])->toBe($fyWithEntries->id);
});

test('collection returns null preferred_fiscal_year_id when no fiscal years exist', function () {
    $paginated = FiscalYear::orderBy('start_date', 'desc')->paginate(15);
    $response = (new FiscalYearCollection($paginated))->response()->getData(true);

    expect($response['meta']['preferred_fiscal_year_id'])->toBeNull();
});
