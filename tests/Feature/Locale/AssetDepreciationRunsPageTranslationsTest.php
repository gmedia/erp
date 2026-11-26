<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, mixed>
 */
function localeJsonFile(string $file): array
{
    return json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);
}

test('asset depreciation runs page keys exist in both english and indonesian locales', function () {
    $en = localeJsonFile('en.json')['page']['asset_depreciation_runs'];
    $id = localeJsonFile('id.json')['page']['asset_depreciation_runs'];

    expect(array_diff(array_keys($en), array_keys($id)))->toBe([])
        ->and(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('english asset depreciation runs page labels keep the original english text', function () {
    $en = localeJsonFile('en.json')['page']['asset_depreciation_runs'];

    expect($en['run_calculation'])->toBe('Run Calculation')
        ->and($en['col_fiscal_year'])->toBe('Fiscal Year')
        ->and($en['col_period'])->toBe('Period')
        ->and($en['col_lines'])->toBe('Lines')
        ->and($en['col_status'])->toBe('Status')
        ->and($en['col_journal'])->toBe('Journal')
        ->and($en['col_created_by'])->toBe('Created By')
        ->and($en['period_to'])->toBe('to')
        ->and($en['lines_count'])->toBe('{count} Assets')
        ->and($en['status_posted'])->toBe('posted');
});

test('indonesian asset depreciation runs page labels are translated', function () {
    $en = localeJsonFile('en.json')['page']['asset_depreciation_runs'];
    $id = localeJsonFile('id.json')['page']['asset_depreciation_runs'];

    foreach (array_keys($en) as $key) {
        expect($id[$key])->not->toBe('');
    }

    expect($id['run_calculation'])->not->toBe($en['run_calculation'])
        ->and($id['col_fiscal_year'])->not->toBe($en['col_fiscal_year'])
        ->and($id['col_created_by'])->not->toBe($en['col_created_by'])
        ->and($id['lines_count'])->toContain('{count}')
        ->and($id['status_posted'])->toBe('Diposting');
});

test('dashboard purchase orders shortcut uses an existing menu key with matching english text', function () {
    $en = localeJsonFile('en.json');
    $id = localeJsonFile('id.json');

    expect($en['menu']['purchase_order'])->toBe('Purchase Orders')
        ->and($id['menu']['purchase_order'])->not->toBe('Purchase Orders');
});
