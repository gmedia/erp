<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string>
 */
function localeAccountsToastTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['accounts_toast'] ?? [];
}

test('accounts_toast translation keys exist in both english and indonesian locales', function () {
    $en = localeAccountsToastTranslations('en.json');
    $id = localeAccountsToastTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('accounts_toast english messages keep the original english text', function () {
    expect(localeAccountsToastTranslations('en.json'))->toBe([
        'coa_versions_fetch_failed' => 'Failed to fetch COA versions',
        'accounts_fetch_failed' => 'Failed to fetch accounts',
        'account_updated' => 'Account updated successfully',
        'account_created' => 'Account created successfully',
        'account_save_failed' => 'Failed to save account',
        'account_deleted' => 'Account deleted successfully',
        'account_delete_failed' => 'Failed to delete account',
        'unexpected_error' => 'An unexpected error occurred',
    ]);
});

test('accounts_toast indonesian messages are translated, not copied from english', function () {
    $en = localeAccountsToastTranslations('en.json');
    $id = localeAccountsToastTranslations('id.json');

    foreach (array_keys($en) as $key) {
        expect($id[$key])->not->toBe($en[$key], "accounts_toast.{$key} not translated");
    }
});

test('accounts page reads accounts_toast keys and has no english toast literals', function () {
    $source = File::get(resource_path('js/pages/accounts/index.tsx'));

    expect(preg_match("/toast\.(success|error)\(\s*['`\"][A-Z]/", $source))
        ->toBe(0, 'accounts page masih memakai toast English literal');
    expect($source)->toContain("useToastTranslator('accounts_toast')");

    foreach (array_keys(localeAccountsToastTranslations('en.json')) as $key) {
        expect(str_contains($source, "'{$key}'"))->toBeTrue("accounts_toast.{$key} tidak dipakai halaman");
    }
});
