<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, mixed>
 */
function issue599LocaleJson(string $file): array
{
    return json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);
}

function issue599LocaleLookup(array $locale, string $dottedKey): ?string
{
    $node = $locale;

    foreach (explode('.', $dottedKey) as $segment) {
        if (! is_array($node) || ! array_key_exists($segment, $node)) {
            return null;
        }

        $node = $node[$segment];
    }

    return is_string($node) ? $node : null;
}

/**
 * @return list<string>
 */
function issue599HelperKeys(): array
{
    $source = File::get(resource_path('js/components/common/filters.tsx'));

    preg_match_all("/key: '(filter_field\\.[a-z_0-9]+)'/", $source, $matches);

    return array_values(array_unique($matches[1]));
}

test('issue 599 shared filter helper keys exist in both locales', function () {
    $en = issue599LocaleJson('en.json');
    $id = issue599LocaleJson('id.json');

    $keys = issue599HelperKeys();

    expect($keys)->not->toBeEmpty();

    foreach ($keys as $key) {
        expect(issue599LocaleLookup($en, $key))->not->toBeNull("en is missing {$key}");
        expect(issue599LocaleLookup($id, $key))->not->toBeNull("id is missing {$key}");
    }
});

test('issue 599 shared filter helper english fallbacks match the en locale', function () {
    $en = issue599LocaleJson('en.json');

    expect(issue599LocaleLookup($en, 'filter_field.all_warehouses'))->toBe('All warehouses')
        ->and(issue599LocaleLookup($en, 'filter_field.select_warehouse'))->toBe('Select warehouse')
        ->and(issue599LocaleLookup($en, 'filter_field.all_suppliers'))->toBe('All suppliers')
        ->and(issue599LocaleLookup($en, 'filter_field.start_date'))->toBe('Start Date')
        ->and(issue599LocaleLookup($en, 'filter_field.min_cost'))->toBe('Min Cost')
        ->and(issue599LocaleLookup($en, 'filter_field.search_code_name'))->toBe('Search code, name...');
});

test('issue 599 shared filter helper labels are translated to indonesian', function () {
    $id = issue599LocaleJson('id.json');

    expect(issue599LocaleLookup($id, 'filter_field.all_warehouses'))->toBe('Semua gudang')
        ->and(issue599LocaleLookup($id, 'filter_field.select_warehouse'))->toBe('Pilih gudang')
        ->and(issue599LocaleLookup($id, 'filter_field.all_suppliers'))->toBe('Semua pemasok')
        ->and(issue599LocaleLookup($id, 'filter_field.start_date'))->toBe('Tanggal Mulai');
});

test('issue 599 shared filter helper has no literal english label or placeholder', function () {
    $source = File::get(resource_path('js/components/common/filters.tsx'));

    // Literal string sebagai label/placeholder field filter (bukan { key, fallback }).
    preg_match_all("/\\b(?:label|placeholder)\\s*:\\s*'([A-Z][^']*)'/", $source, $literal);

    $offenders = array_values(array_filter(
        $literal[1],
        fn (string $text) => ! preg_match('/^[A-Z]{3}$/', $text),
    ));

    expect($offenders)->toBe([]);
});
