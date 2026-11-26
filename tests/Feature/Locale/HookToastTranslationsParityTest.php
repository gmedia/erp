<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

/**
 * @return array<string, string>
 */
function localeHookToastTranslations(string $file): array
{
    $decoded = json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    return $decoded['hook_toast'] ?? [];
}

test('hook_toast translation keys exist in both english and indonesian locales', function () {
    $en = localeHookToastTranslations('en.json');
    $id = localeHookToastTranslations('id.json');

    expect($en)->not->toBeEmpty();
    expect(array_diff(array_keys($en), array_keys($id)))->toBe([]);
    expect(array_diff(array_keys($id), array_keys($en)))->toBe([]);
});

test('hook_toast english messages keep the original english text', function () {
    $en = localeHookToastTranslations('en.json');

    expect($en)->toBe([
        'unexpected_error' => 'An unexpected error occurred',
        'approval_history_load_failed' => 'Failed to load approval history',
        'pipeline_state_fetch_failed' => 'Failed to fetch pipeline states.',
        'pipeline_state_created' => 'Pipeline state created successfully.',
        'pipeline_state_create_failed' => 'Failed to create pipeline state.',
        'pipeline_state_updated' => 'Pipeline state updated successfully.',
        'pipeline_state_update_failed' => 'Failed to update pipeline state.',
        'pipeline_state_deleted' => 'Pipeline state deleted successfully.',
        'pipeline_state_delete_failed' => 'Failed to delete pipeline state.',
        'pipeline_transition_fetch_failed' => 'Failed to fetch pipeline transitions. Please try again.',
        'pipeline_transition_created' => 'Pipeline transition created successfully.',
        'pipeline_transition_create_failed' => 'Failed to create pipeline transition.',
        'pipeline_transition_updated' => 'Pipeline transition updated successfully.',
        'pipeline_transition_update_failed' => 'Failed to update pipeline transition.',
        'pipeline_transition_deleted' => 'Pipeline transition deleted successfully.',
        'pipeline_transition_delete_failed' => 'Failed to delete pipeline transition.',
        'post_journals_failed' => 'Failed to post journals',
        'export_completed' => 'Export completed successfully',
        'export_failed' => 'Failed to export data. Please try again.',
        'entity_state_fetch_failed' => 'Failed to fetch entity state',
        'timeline_fetch_failed' => 'Failed to fetch timeline',
        'transition_failed' => 'Transition failed',
        'transition_successful' => 'Transition successful',
        'user_fetch_failed' => 'Failed to fetch user data.',
        'user_saved' => 'User saved successfully.',
        'user_validation_failed' => 'Please fix the validation errors.',
        'user_save_failed' => 'Failed to save user.',
        'employee_permissions_fetch_failed' => 'Failed to fetch employee permissions.',
        'employee_permissions_updated' => 'Permissions updated successfully.',
        'employee_permissions_update_failed' => 'Failed to update permissions.',
        'stocktake_items_fetch_failed' => 'Failed to fetch stocktake items.',
        'stocktake_items_saved' => 'Stocktake items saved successfully.',
        'stocktake_items_save_failed' => 'Failed to save items.',
    ]);
});

test('hook_toast indonesian messages are translated, not copied from english', function () {
    $en = localeHookToastTranslations('en.json');
    $id = localeHookToastTranslations('id.json');

    foreach (array_keys($en) as $key) {
        expect($id[$key])->not->toBe($en[$key], "hook_toast.{$key} not translated");
    }
});

test('toast-producing hooks in scope read hook_toast keys with english fallbacks', function () {
    $hooks = [
        'hooks/usePostingJournal.ts',
        'hooks/usePipelineTransition.ts',
        'hooks/usePipelineState.ts',
        'hooks/useExport.ts',
        'hooks/useEntityPipeline.ts',
        'hooks/useEntityApprovalHistory.ts',
        'hooks/users/useEmployeeUser.ts',
        'hooks/permissions/useEmployeePermissions.ts',
        'hooks/asset-stocktakes/useStocktakeItems.ts',
    ];

    $en = localeHookToastTranslations('en.json');

    foreach ($hooks as $hook) {
        $source = File::get(resource_path('js/' . $hook));

        // Tidak boleh ada toast English literal yang tidak diterjemahkan.
        expect(preg_match("/toast\.(success|error)\(\s*['`\"][A-Z]/", $source))
            ->toBe(0, "{$hook} masih memakai toast English literal");

        expect($source)->toMatch('/hook_toast\.|tToast\(/', "{$hook} tidak memakai kunci hook_toast");
    }

    // Setiap kunci en harus dipakai oleh setidaknya satu hook dalam lingkup.
    $allSource = collect($hooks)
        ->map(fn (string $hook) => File::get(resource_path('js/' . $hook)))
        ->implode("\n");

    foreach (array_keys($en) as $key) {
        expect(str_contains($allSource, "'{$key}'"))->toBeTrue("hook_toast.{$key} tidak dipakai hook mana pun");
    }
});
