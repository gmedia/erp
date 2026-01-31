<?php

use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('fiscal-years');

test('factory creates a valid fiscal year', function () {
    $fiscalYear = FiscalYear::factory()->create();

    expect($fiscalYear->id)->not->toBeNull()
        ->and($fiscalYear->name)->not->toBeEmpty()
        ->and($fiscalYear->start_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($fiscalYear->end_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($fiscalYear->status)->not->toBeEmpty();
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
