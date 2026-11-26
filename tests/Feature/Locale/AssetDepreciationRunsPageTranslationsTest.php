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

/**
 * Seluruh kunci UI halaman Asset Depreciation Runs (page, form_fields, toast).
 *
 * @return array<string, string>
 */
function assetDepreciationRunsStrings(string $file): array
{
    $json = localeJsonFile($file);

    return array_merge(
        $json['page']['asset_depreciation_runs'],
        $json['form_fields']['asset_depreciation_runs'],
        $json['asset_depreciation_runs_toast'],
    );
}

test('asset depreciation runs page keys exist in both english and indonesian locales', function () {
    $en = localeJsonFile('en.json')['page']['asset_depreciation_runs'];
    $id = localeJsonFile('id.json')['page']['asset_depreciation_runs'];

    expect(array_diff(array_keys($en), array_keys($id)))->toBe([])
        ->and(array_diff(array_keys($id), array_keys($en)))->toBe([]);

    $enForm = localeJsonFile('en.json')['form_fields']['asset_depreciation_runs'];
    $idForm = localeJsonFile('id.json')['form_fields']['asset_depreciation_runs'];

    expect(array_diff(array_keys($enForm), array_keys($idForm)))->toBe([])
        ->and(array_diff(array_keys($idForm), array_keys($enForm)))->toBe([]);
});

test('english asset depreciation runs labels keep the original english text', function () {
    $en = assetDepreciationRunsStrings('en.json');

    expect($en['run_calculation'])->toBe('Run Calculation')
        ->and($en['col_fiscal_year'])->toBe('Fiscal Year')
        ->and($en['col_period'])->toBe('Period')
        ->and($en['col_lines'])->toBe('Lines')
        ->and($en['col_status'])->toBe('Status')
        ->and($en['col_journal'])->toBe('Journal')
        ->and($en['col_created_by'])->toBe('Created By')
        ->and($en['period_to'])->toBe('to')
        ->and($en['lines_count'])->toBe('{count} Assets')
        ->and($en['status_posted'])->toBe('posted')
        ->and($en['breadcrumb_dashboard'])->toBe('Dashboard')
        ->and($en['breadcrumb_asset_management'])->toBe('Asset Management')
        ->and($en['breadcrumb_depreciation_runs'])->toBe('Depreciation Runs')
        ->and($en['void_action'])->toBe('Void')
        ->and($en['void_title'])->toBe('Void depreciation run?')
        ->and($en['void_confirm'])->toBe('Void run')
        ->and($en['voiding'])->toBe('Voiding...')
        ->and($en['loading_runs'])->toBe('Loading runs...')
        ->and($en['no_runs_title'])->toBe('No runs found')
        ->and($en['no_runs_description'])->toBe('Start by running a new calculation.')
        ->and($en['calculate_now'])->toBe('Calculate Now')
        ->and($en['post_action'])->toBe('Post')
        ->and($en['actions_label'])->toBe('Actions')
        ->and($en['run_lines_title'])->toBe('Depreciation Run Lines')
        ->and($en['loading_lines'])->toBe('Loading lines...')
        ->and($en['no_lines'])->toBe('No lines found.')
        ->and($en['col_asset'])->toBe('Asset')
        ->and($en['col_book_value_after'])->toBe('Book Value After')
        ->and($en['cancel'])->toBe('Cancel')
        ->and($en['calculate_fallback_error'])->toBe('An error occurred.');
});

test('indonesian asset depreciation runs labels are translated', function () {
    $en = assetDepreciationRunsStrings('en.json');
    $id = assetDepreciationRunsStrings('id.json');

    foreach (array_keys($en) as $key) {
        expect($id[$key])->not->toBe('', "{$key} is empty in id");
    }

    expect($id['run_calculation'])->not->toBe($en['run_calculation'])
        ->and($id['col_created_by'])->not->toBe($en['col_created_by'])
        ->and($id['lines_count'])->toContain('{count}')
        ->and($id['status_posted'])->toBe('Diposting')
        ->and($id['void_title'])->not->toBe($en['void_title'])
        ->and($id['loading_runs'])->not->toBe($en['loading_runs'])
        ->and($id['no_runs_title'])->not->toBe($en['no_runs_title']);
});

test('status column label stays Status in indonesian', function () {
    $id = localeJsonFile('id.json')['page']['asset_depreciation_runs'];

    expect($id['col_status'])->toBe('Status');
});

test('dashboard purchase orders shortcut uses an existing menu key with matching english text', function () {
    $en = localeJsonFile('en.json');
    $id = localeJsonFile('id.json');

    expect($en['menu']['purchase_order'])->toBe('Purchase Orders')
        ->and($id['menu']['purchase_order'])->not->toBe('Purchase Orders');
});
