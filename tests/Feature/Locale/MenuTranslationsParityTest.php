<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string> menu name => display_name, dari MenuSeeder
 */
function seededMenuDisplayNames(): array
{
    $source = File::get(base_path('database/seeders/MenuSeeder.php'));

    preg_match_all(
        "/'name'\\s*=>\\s*'([^']+)',\\s*'display_name'\\s*=>\\s*'([^']+)'/",
        $source,
        $matches,
        PREG_SET_ORDER,
    );

    $menus = [];
    foreach ($matches as $match) {
        $menus[$match[1]] = $match[2];
    }

    return $menus;
}

/**
 * @return array<string, string>
 */
function localeMenuTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['menu'] ?? [];
}

test('english menu translations match seeded menu display names exactly', function () {
    $seeded = seededMenuDisplayNames();
    $en = localeMenuTranslations('en.json');

    expect($seeded)->not->toBeEmpty();

    $mismatches = [];
    foreach ($seeded as $name => $displayName) {
        if (! array_key_exists($name, $en)) {
            $mismatches[] = "missing en key: {$name}";

            continue;
        }

        if ($en[$name] !== $displayName) {
            $mismatches[] = "{$name}: en=\"{$en[$name]}\" seeder=\"{$displayName}\"";
        }
    }

    expect($mismatches)->toBe([]);
});

test('every english menu translation key has a seeded menu', function () {
    $seeded = seededMenuDisplayNames();
    $en = localeMenuTranslations('en.json');

    expect(array_diff(array_keys($en), array_keys($seeded)))->toBe([]);
});

test('every menu translation key exists in indonesian locale', function () {
    $en = localeMenuTranslations('en.json');
    $id = localeMenuTranslations('id.json');

    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});
