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
        ->and($en['user_guide'])->toBe('User Guide');
});

test('shell translation values are non-empty strings', function () {
    foreach (['en.json', 'id.json'] as $file) {
        foreach (localeShellTranslations($file) as $key => $value) {
            expect($value)->toBeString()->and(trim($value))->not->toBe('', "{$file} shell.{$key} is empty");
        }
    }
});
