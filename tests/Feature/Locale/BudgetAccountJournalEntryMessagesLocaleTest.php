<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('locale');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'budget', 'budget.edit', 'budget.delete',
        'account', 'account.edit', 'account.delete',
        'journal_entry', 'journal_entry.delete',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('budget approve message defaults to english without locale header', function () {
    $budget = Budget::factory()->create(['status' => 'locked']);

    postJson("/api/budgets/{$budget->id}/approve")
        ->assertStatus(422)
        ->assertJsonPath('message', 'Only draft budgets can be approved.');
});

test('budget approve message is indonesian with X-Locale id', function () {
    $budget = Budget::factory()->create(['status' => 'locked']);

    postJson("/api/budgets/{$budget->id}/approve", [], ['X-Locale' => 'id'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Hanya Anggaran berstatus draf yang dapat disetujui.');
});

test('account delete message is indonesian with X-Locale id when account has child accounts', function () {
    $parent = Account::factory()->create();
    Account::factory()->create(['parent_id' => $parent->id]);

    deleteJson("/api/accounts/{$parent->id}", [], ['X-Locale' => 'id'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Akun yang memiliki sub-akun tidak dapat dihapus.');
});

test('account delete message defaults to english when account has child accounts', function () {
    $parent = Account::factory()->create();
    Account::factory()->create(['parent_id' => $parent->id]);

    deleteJson("/api/accounts/{$parent->id}")
        ->assertStatus(422)
        ->assertJsonPath('message', 'Cannot delete account with child accounts.');
});

test('journal entry delete message is indonesian with X-Locale id when posted', function () {
    $entry = JournalEntry::factory()->create(['status' => 'posted']);

    deleteJson("/api/journal-entries/{$entry->id}", [], ['X-Locale' => 'id'])
        ->assertStatus(403)
        ->assertJsonPath('message', 'Jurnal Umum yang sudah diposting tidak dapat dihapus.');
});

test('budget account and journal entry message keys exist in both locales with translations', function () {
    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);
    $id = json_decode(File::get(lang_path('id.json')), true, 512, JSON_THROW_ON_ERROR);

    $keys = array_filter(
        array_keys($en),
        fn (string $key) => str_starts_with($key, 'budget_api.')
            || str_starts_with($key, 'account_api.')
            || str_starts_with($key, 'journal_entry_api.'),
    );

    expect($keys)->toHaveCount(6);
    foreach ($keys as $key) {
        expect(array_key_exists($key, $id))->toBeTrue("{$key} missing in id.json");
        expect($id[$key])->not->toBe($en[$key], "{$key} not translated");
    }
});
