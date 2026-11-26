<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string>
 */
function localeAuthToastTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['auth_toast'] ?? [];
}

test('auth_toast translation keys exist in both english and indonesian locales', function () {
    $en = localeAuthToastTranslations('en.json');
    $id = localeAuthToastTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('auth_toast english messages keep the original english text', function () {
    $en = localeAuthToastTranslations('en.json');

    expect($en)->toBe([
        'generic_retry' => 'An error occurred. Please try again.',
        'reset_password_retry' => 'An error occurred. Please try again later.',
        'verification_link_sent' => 'Verification link sent',
        'verification_link_failed' => 'Failed to send verification link.',
        'logout_failed' => 'Failed to log out.',
    ]);
});

test('auth_toast indonesian messages are translated, not copied from english', function () {
    $en = localeAuthToastTranslations('en.json');
    $id = localeAuthToastTranslations('id.json');

    foreach (array_keys($en) as $key) {
        expect($id[$key])->not->toBe($en[$key], "auth_toast.{$key} not translated");
    }
});

test('auth toast sources read auth_toast keys with english fallbacks', function () {
    $sources = [
        'pages/auth/confirm-password.tsx',
        'pages/auth/reset-password.tsx',
        'pages/auth/two-factor-challenge.tsx',
        'pages/auth/verify-email.tsx',
    ];

    $en = localeAuthToastTranslations('en.json');
    $allSource = '';

    foreach ($sources as $source) {
        $content = File::get(resource_path('js/' . $source));
        $allSource .= $content;

        // Tidak boleh ada toast English literal yang tidak diterjemahkan.
        expect(preg_match("/toast\.(success|error)\(\s*['`\"][A-Z]/", $content))
            ->toBe(0, "{$source} masih memakai toast English literal");

        expect($content)->toContain("useToastTranslator('auth_toast')");
    }

    // Setiap kunci en harus dipakai oleh setidaknya satu sumber dalam lingkup.
    foreach (array_keys($en) as $key) {
        expect(str_contains($allSource, "'{$key}'"))->toBeTrue();
    }
});
