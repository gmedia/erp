<?php

use App\Http\Resources\FiscalYears\FiscalYearCollection;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

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

test('collection respects status filter when computing preferred_fiscal_year_id', function () {
    $closedFiscalYear = FiscalYear::factory()->create([
        'name' => '2024',
        'status' => 'closed',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
    ]);
    $openFiscalYear = FiscalYear::factory()->create([
        'name' => '2025',
        'status' => 'open',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    JournalEntry::factory()->create([
        'fiscal_year_id' => $closedFiscalYear->id,
        'status' => 'posted',
    ]);

    $request = Request::create('/api/fiscal-years', 'GET', ['status' => 'open']);
    $paginated = FiscalYear::orderBy('start_date', 'desc')->paginate(15);
    $response = (new FiscalYearCollection($paginated))
        ->toResponse($request)
        ->getData(true);

    expect($response['meta']['preferred_fiscal_year_id'])->toBe($openFiscalYear->id);
});

test('collection ignores empty status filter when computing preferred_fiscal_year_id', function () {
    $closedFiscalYear = FiscalYear::factory()->create([
        'name' => '2024',
        'status' => 'closed',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
    ]);
    FiscalYear::factory()->create([
        'name' => '2025',
        'status' => 'open',
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
    ]);

    JournalEntry::factory()->create([
        'fiscal_year_id' => $closedFiscalYear->id,
        'status' => 'posted',
    ]);

    $request = Request::create('/api/fiscal-years', 'GET', ['status' => '']);
    $paginated = FiscalYear::orderBy('start_date', 'desc')->paginate(15);
    $response = (new FiscalYearCollection($paginated))
        ->toResponse($request)
        ->getData(true);

    expect($response['meta']['preferred_fiscal_year_id'])->toBe($closedFiscalYear->id);
});
