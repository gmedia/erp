<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, mixed>
 */
function localeToastNamespace(string $file, string $namespace): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded[$namespace] ?? [];
}

test('settings, bank reconciliation and push toast keys exist in both english and indonesian locales', function () {
    foreach (['settings_toast', 'bank_reconciliation_toast', 'push_toast'] as $namespace) {
        $en = localeToastNamespace('en.json', $namespace);
        $id = localeToastNamespace('id.json', $namespace);

        expect($en)->not->toBeEmpty();
        expect(array_diff(array_keys($en), array_keys($id)))->toBe([], "{$namespace} kurang di id");
        expect(array_diff(array_keys($id), array_keys($en)))->toBe([], "{$namespace} kelebihan di id");
    }
});

test('new settings toast english messages keep the original english text', function () {
    $en = localeToastNamespace('en.json', 'settings_toast');

    expect($en)
        ->toHaveKey('password_updated', 'Password updated successfully')
        ->toHaveKey('password_update_failed', 'An error occurred. Please try again later.')
        ->toHaveKey('two_factor_enable_failed', 'Failed to enable two-factor authentication.')
        ->toHaveKey('two_factor_disable_failed', 'Failed to disable two-factor authentication.');
});

test('bank reconciliation toast english messages keep the original english text', function () {
    $en = localeToastNamespace('en.json', 'bank_reconciliation_toast');

    expect($en)
        ->toHaveKey('items_load_failed', 'Failed to load reconciliation items.')
        ->toHaveKey('auto_match_complete', 'Auto Match Complete')
        ->toHaveKey('auto_match_result', 'Matched: {matched}, Unmatched: {unmatched}')
        ->toHaveKey('account_assigned', 'Account assigned: {code} - {name}')
        ->toHaveKey('reconciliation_completed', 'Reconciliation Completed')
        ->toHaveKey('complete_failed', 'Complete Failed')
        ->toHaveKey('invalid_file', 'Invalid File')
        ->toHaveKey('upload_failed', 'Upload Failed')
        ->toHaveKey('validation_error', 'Validation Error')
        ->toHaveKey('import_failed', 'Import Failed')
        ->toHaveKey('incomplete_mapping', 'Incomplete Mapping')
        ->toHaveKey('import_success_count', 'Successfully imported {count} transactions.')
        ->toHaveKey('import_finished_with_errors', 'Import Finished with Errors');
});

test('push toast english messages keep the original english text', function () {
    $en = localeToastNamespace('en.json', 'push_toast');

    expect($en)->toBe([
        'enabled' => 'Push notifications enabled.',
        'register_device_failed' => 'Failed to register this device.',
        'firebase_config_failed' => 'Failed to enable notifications. Check the Firebase configuration.',
        'new_notification' => 'New notification',
    ]);
});

test('settings, bank reconciliation and push toast indonesian messages are translated, not copied from english', function () {
    foreach (['settings_toast', 'bank_reconciliation_toast', 'push_toast'] as $namespace) {
        $en = localeToastNamespace('en.json', $namespace);
        $id = localeToastNamespace('id.json', $namespace);

        foreach (array_keys($en) as $key) {
            expect($id[$key])->not->toBe($en[$key], "{$namespace}.{$key} belum diterjemahkan");
        }
    }
});

test('toast placeholders match between english and indonesian', function () {
    foreach (['settings_toast', 'bank_reconciliation_toast', 'push_toast'] as $namespace) {
        $en = localeToastNamespace('en.json', $namespace);
        $id = localeToastNamespace('id.json', $namespace);

        foreach ($en as $key => $text) {
            preg_match_all('/\{(\w+)\}/', $text, $enParams);
            preg_match_all('/\{(\w+)\}/', $id[$key], $idParams);

            expect($idParams[1])->toEqualCanonicalizing($enParams[1], "{$namespace}.{$key} placeholder beda");
        }
    }
});

test('toast-producing files in scope read namespaced keys instead of english literals', function () {
    $files = [
        'pages/settings/password.tsx' => "/settings_toast\\.|useToastTranslator\\('settings_toast'\\)/",
        'pages/settings/two-factor.tsx' => "/useToastTranslator\\('settings_toast'\\)/",
        'components/bank-reconciliations/BankReconciliationWorkspace.tsx' => "/useToastTranslator\\('bank_reconciliation_toast'\\)/",
        'components/bank-reconciliations/ImportBankStatementDialog.tsx' => "/useToastTranslator\\('bank_reconciliation_toast'\\)/",
        'lib/push-notifications.ts' => '/push_toast\\./',
    ];

    foreach ($files as $file => $pattern) {
        $source = File::get(resource_path('js/' . $file));

        // Tidak boleh ada toast English literal yang belum diterjemahkan.
        expect(preg_match("/toast\\.(success|error|info)\\(\\s*['`\"][A-Z]/", $source))
            ->toBe(0, "{$file} masih memakai toast English literal");

        expect($source)->toMatch($pattern, "{$file} tidak memakai namespace toast");
    }
});
