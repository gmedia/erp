<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string>
 */
function localeDepreciationRunFormFields(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['form_fields']['asset_depreciation_runs'] ?? [];
}

test('asset depreciation run form keys exist in both english and indonesian locales', function () {
    $en = localeDepreciationRunFormFields('en.json');
    $id = localeDepreciationRunFormFields('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([])
        ->and(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('english asset depreciation run form labels keep the original english text', function () {
    $en = localeDepreciationRunFormFields('en.json');

    expect($en['calculate_title'])->toBe('Calculate Depreciation')
        ->and($en['calculate_description'])->toBe('Select the fiscal year and period to calculate asset depreciation.')
        ->and($en['fiscal_year'])->toBe('Fiscal Year')
        ->and($en['fiscal_year_placeholder'])->toBe('Select Fiscal Year...')
        ->and($en['period_start'])->toBe('Period Start')
        ->and($en['period_start_placeholder'])->toBe('Pick period start')
        ->and($en['period_end'])->toBe('Period End')
        ->and($en['period_end_placeholder'])->toBe('Pick period end')
        ->and($en['calculate'])->toBe('Calculate');
});

test('english shared add item label keeps the original english text', function () {
    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);
    $id = json_decode(File::get(lang_path('id.json')), true, 512, JSON_THROW_ON_ERROR);

    expect($en['shell']['add_item'])->toBe('Add Item')
        ->and($id['shell']['add_item'])->toBe('Tambah Item');
});
