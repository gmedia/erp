<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, mixed>
 */
function localeJsonTranslations(string $file): array
{
    return json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);
}

/**
 * @return array<int, array{name: string, key: string}>
 */
function crudEntityNameKeysFromConfigs(): array
{
    $source = File::get(resource_path('js/utils/entityConfigs.ts'));

    preg_match_all(
        "/entityName: '([^']+)',\\s*\\n\\s*entityNameKey: '([^']+)',/",
        $source,
        $matches,
        PREG_SET_ORDER,
    );

    return array_map(
        static fn (array $match): array => ['name' => $match[1], 'key' => $match[2]],
        $matches,
    );
}

/**
 * @return array<string, string>
 */
function crudEntityNameLookup(string $file, string $key): array
{
    $translations = localeJsonTranslations($file);

    if (str_starts_with($key, 'menu.')) {
        return ['value' => $translations['menu'][substr($key, 5)] ?? ''];
    }

    return ['value' => $translations['crud_entity'][substr($key, strlen('crud_entity.'))] ?? ''];
}

test('every crud entity config declares an entityNameKey', function () {
    $entries = crudEntityNameKeysFromConfigs();

    expect(count($entries))->toBe(43);
});

test('entity name keys resolve in both english and indonesian locales', function () {
    foreach (crudEntityNameKeysFromConfigs() as $entry) {
        $en = crudEntityNameLookup('en.json', $entry['key'])['value'];
        $id = crudEntityNameLookup('id.json', $entry['key'])['value'];

        expect($en)->not->toBe('', "{$entry['key']} missing in en.json")
            ->and($id)->not->toBe('', "{$entry['key']} missing in id.json");
    }
});

test('english entity name keys keep the exact english entity name for toasts', function () {
    foreach (crudEntityNameKeysFromConfigs() as $entry) {
        $en = crudEntityNameLookup('en.json', $entry['key'])['value'];

        expect($en)->toBe($entry['name'], "{$entry['key']} english drifted from entityName");
    }
});

test('indonesian entity names differ from english for every crud_entity key', function () {
    $en = localeJsonTranslations('en.json')['crud_entity'];
    $id = localeJsonTranslations('id.json')['crud_entity'];

    expect(array_keys($en))->toEqual(array_keys($id));

    foreach ($en as $key => $value) {
        expect($id[$key])->not->toBe($value, "crud_entity.{$key} not translated");
    }
});

test('crud_entity translation keys exist in both locales', function () {
    $en = localeJsonTranslations('en.json')['crud_entity'];
    $id = localeJsonTranslations('id.json')['crud_entity'];

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('crud entity config builders forward entityNameKey', function () {
    $source = File::get(resource_path('js/utils/entityConfigs.ts'));

    expect(substr_count($source, 'entityNameKey: options.entityNameKey'))->toBe(1)
        ->and(substr_count($source, "        entityNameKey,\n"))->toBe(2);
});

test('crud page translates entityNameKey into the toast entity name', function () {
    $crudPage = File::get(resource_path('js/components/common/CrudPage.tsx'));
    $mutations = File::get(resource_path('js/hooks/useCrudMutations.ts'));
    $query = File::get(resource_path('js/hooks/useCrudQuery.ts'));

    expect($crudPage)->toContain('translateOr(t, config.entityNameKey, config.entityName)')
        ->and($crudPage)->toContain('toastEntityName')
        ->and($mutations)->toContain('const displayName = toastEntityName ?? entityName;')
        ->and($query)->toContain('const displayName = toastEntityName ?? entityName;');
});
