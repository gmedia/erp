<?php

use App\Models\CoaVersion;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('coa-versions');

test('factory creates a valid coa version', function () {
    $coaVersion = CoaVersion::factory()->create();

    expect($coaVersion->id)->not->toBeNull()
        ->and($coaVersion->name)->not->toBeEmpty()
        ->and($coaVersion->fiscal_year_id)->not->toBeNull()
        ->and($coaVersion->status)->not->toBeEmpty();
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new CoaVersion)->getFillable();

    expect($fillable)->toContain(
        'name',
        'fiscal_year_id',
        'status',
    );
});

test('belongs to a fiscal year', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $coaVersion = CoaVersion::factory()->create(['fiscal_year_id' => $fiscalYear->id]);

    expect($coaVersion->fiscalYear)->toBeInstanceOf(FiscalYear::class)
        ->and($coaVersion->fiscalYear->id)->toBe($fiscalYear->id);
});
