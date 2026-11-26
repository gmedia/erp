<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string>
 */
function localeCrudToastTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['crud_toast'] ?? [];
}

test('crud_toast translation keys exist in both english and indonesian locales', function () {
    $en = localeCrudToastTranslations('en.json');
    $id = localeCrudToastTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('crud_toast english messages keep the original english text', function () {
    $en = localeCrudToastTranslations('en.json');

    expect($en)->toBe([
        'created' => '{entity} created successfully',
        'updated' => '{entity} updated successfully',
        'deleted' => '{entity} deleted successfully',
        'create_failed' => 'Failed to create {entity}',
        'update_failed' => 'Failed to update {entity}',
        'delete_failed' => 'Failed to delete {entity}',
        'load_failed' => 'Failed to load {entity}',
    ]);
});

test('crud_toast messages are translated to indonesian and keep the entity placeholder', function () {
    $en = localeCrudToastTranslations('en.json');
    $id = localeCrudToastTranslations('id.json');

    foreach (array_keys($en) as $key) {
        expect($id[$key])->not->toBe($en[$key], "crud_toast.{$key} not translated")
            ->and($id[$key])->toContain('{entity}');
    }
});

test('crud hooks use crud_toast keys with english fallbacks', function () {
    $mutations = File::get(resource_path('js/hooks/useCrudMutations.ts'));
    $query = File::get(resource_path('js/hooks/useCrudQuery.ts'));
    $en = localeCrudToastTranslations('en.json');

    foreach (['created', 'updated', 'deleted'] as $key) {
        expect($mutations)->toContain("tToast('{$key}'");
    }

    foreach (['create_failed', 'update_failed', 'delete_failed'] as $key) {
        expect($mutations)->toContain("tToast('{$key}'");
    }

    expect($query)->toContain("'crud_toast.load_failed'");

    foreach ($en as $key => $template) {
        $fallback = str_replace('{entity}', '${entityName}', $template);
        expect(str_contains($mutations . $query, "`{$fallback}`"))->toBeTrue("crud_toast.{$key} fallback drifted");
    }
});
