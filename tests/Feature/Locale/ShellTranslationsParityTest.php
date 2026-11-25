<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string>
 */
function localeShellTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['shell'] ?? [];
}

test('shell translation keys exist in both english and indonesian locales', function () {
    $en = localeShellTranslations('en.json');
    $id = localeShellTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('english shell translations keep the original english toolbar labels', function () {
    $en = localeShellTranslations('en.json');

    expect($en['filters'])->toBe('Filters')
        ->and($en['add'])->toBe('Add')
        ->and($en['export'])->toBe('Export')
        ->and($en['columns'])->toBe('Columns')
        ->and($en['user_guide'])->toBe('User Guide')
        ->and($en['refresh'])->toBe('Refresh')
        ->and($en['import'])->toBe('Import')
        ->and($en['import_title'])->toBe('Import Data')
        ->and($en['import_success_rows'])->toBe('Successfully imported {count} rows.');
});

test('english table empty state keeps the original text', function () {
    $decoded = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);

    expect($decoded['table']['no_results'] ?? null)->toBe('No results.');
});

test('file upload translation keys exist in both locales with matching placeholders', function () {
    $decode = fn (string $file): array => json_decode(
        File::get(lang_path($file)),
        true,
        512,
        JSON_THROW_ON_ERROR,
    )['file_upload'] ?? [];
    $en = $decode('en.json');
    $id = $decode('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_keys($en))->toEqualCanonicalizing(array_keys($id));
    expect($en['choose'])->toBe('Choose File')
        ->and($en['none'])->toBe('No file chosen')
        ->and($id['choose'])->toBe('Pilih Berkas')
        ->and($id['none'])->toBe('Belum ada berkas dipilih');

    foreach (['current', 'preview'] as $key) {
        expect($en[$key])->toContain('{name}')->and($id[$key])->toContain('{name}');
    }
});

test('indonesian locale keeps the import success placeholder', function () {
    $id = localeShellTranslations('id.json');

    expect($id['import_success_rows'])->toContain('{count}');
});

test('shell translation values are non-empty strings', function () {
    foreach (['en.json', 'id.json'] as $file) {
        foreach (localeShellTranslations($file) as $key => $value) {
            expect($value)->toBeString()->and(trim($value))->not->toBe('', "{$file} shell.{$key} is empty");
        }
    }
});
