<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string>
 */
function localePageToastTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['page_toast'] ?? [];
}

test('page_toast translation keys exist in both english and indonesian locales', function () {
    $en = localePageToastTranslations('en.json');
    $id = localePageToastTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('page_toast english messages keep the original english text', function () {
    $en = localePageToastTranslations('en.json');

    expect($en)->toBe([
        'approval_request_approved' => 'Request approved successfully',
        'approval_request_rejected' => 'Request rejected successfully',
        'approval_request_action_failed_approve' => 'Failed to approve request',
        'approval_request_action_failed_reject' => 'Failed to reject request',
        'recovery_codes_regenerated' => 'Recovery codes regenerated successfully.',
        'recovery_codes_regenerate_failed' => 'Failed to regenerate recovery codes.',
    ]);
});

test('page_toast indonesian messages are translated, not copied from english', function () {
    $en = localePageToastTranslations('en.json');
    $id = localePageToastTranslations('id.json');

    foreach (array_keys($en) as $key) {
        expect($id[$key])->not->toBe($en[$key], "page_toast.{$key} not translated");
    }
});

test('page toast sources read page_toast keys with english fallbacks', function () {
    $sources = [
        'pages/my-approvals/index.tsx',
        'components/two-factor-recovery-codes.tsx',
    ];

    $en = localePageToastTranslations('en.json');
    $allSource = '';

    foreach ($sources as $source) {
        $content = File::get(resource_path('js/' . $source));
        $allSource .= $content;

        // Tidak boleh ada toast English literal yang tidak diterjemahkan.
        expect(preg_match("/toast\.(success|error)\(\s*['`\"][A-Z]/", $content))
            ->toBe(0, "{$source} masih memakai toast English literal");

        expect($content)->toContain("useToastTranslator('page_toast')");
    }

    // Setiap kunci en harus dipakai oleh setidaknya satu sumber dalam lingkup.
    foreach (array_keys($en) as $key) {
        expect(str_contains($allSource, "'{$key}'"))->toBeTrue();
    }
});
