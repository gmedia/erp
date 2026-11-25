<?php

use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class)->group('fiscal-years');

test('factory creates a valid fiscal year', function () {
    $fiscalYear = FiscalYear::factory()->create();

    expect($fiscalYear->id)->not->toBeNull()
        ->and($fiscalYear->name)->not->toBeEmpty()
        ->and($fiscalYear->start_date)->toBeInstanceOf(Carbon::class)
        ->and($fiscalYear->end_date)->toBeInstanceOf(Carbon::class)
        ->and($fiscalYear->status)->not->toBeEmpty();
});

test('factory default year never collides with explicit 2026 fixtures', function () {
    for ($i = 0; $i < 60; $i++) {
        $fiscalYear = FiscalYear::factory()->make();

        expect($fiscalYear->start_date->year)->not->toBe(2026)
            ->and($fiscalYear->end_date->year)->toBe($fiscalYear->start_date->year);
    }
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new FiscalYear)->getFillable();

    expect($fillable)->toContain(
        'name',
        'start_date',
        'end_date',
        'status',
    );
});

test('casts are defined correctly', function () {
    $casts = (new FiscalYear)->getCasts();

    expect($casts['start_date'])->toBe('date');
    expect($casts['end_date'])->toBe('date');
});
