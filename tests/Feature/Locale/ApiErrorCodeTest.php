<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
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

test('budget approve returns stable error_code with unchanged english message', function () {
    $budget = Budget::factory()->create(['status' => 'locked']);

    postJson("/api/budgets/{$budget->id}/approve")
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'budget.not_draft')
        ->assertJsonPath('message', 'Only draft budgets can be approved.');
});

test('budget lock returns error_code while message follows X-Locale', function () {
    $budget = Budget::factory()->create(['status' => 'draft']);

    postJson("/api/budgets/{$budget->id}/lock", [], ['X-Locale' => 'id'])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'budget.not_approved')
        ->assertJsonPath('message', 'Hanya Anggaran yang sudah disetujui yang dapat dikunci.');
});

test('account delete returns error_code when account has child accounts', function () {
    $parent = Account::factory()->create();
    Account::factory()->create(['parent_id' => $parent->id]);

    deleteJson("/api/accounts/{$parent->id}")
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'account.has_child_accounts')
        ->assertJsonPath('message', 'Cannot delete account with child accounts.');
});

test('journal entry delete returns error_code when posted', function () {
    $entry = JournalEntry::factory()->create(['status' => 'posted']);

    deleteJson("/api/journal-entries/{$entry->id}")
        ->assertStatus(403)
        ->assertJsonPath('error_code', 'journal_entry.posted_cannot_be_deleted');
});

test('user guide not found returns error_code with unchanged english message', function () {
    getJson('/api/user-guide/does-not-exist')
        ->assertNotFound()
        ->assertJsonPath('error_code', 'user_guide.not_found')
        ->assertJsonPath('message', 'Guide not found.');
});

test('api error code docs list every code used by the backend', function () {
    $docs = File::get(base_path('docs/api-error-codes.md'));
    preg_match_all('/^\| `([a-z_]+\.[a-z_]+)` \|/m', $docs, $matches);
    $documented = array_unique($matches[1]);

    expect($documented)->not->toBeEmpty();

    $source = collect([
        ...File::allFiles(app_path('Http/Controllers')),
        ...File::allFiles(app_path('Http/Middleware')),
    ])
        ->map(fn ($file) => File::get($file->getPathname()))
        ->implode("\n");

    preg_match_all("/ApiErrorException::translated\(\d+, '([a-z_]+\.[a-z_]+)'/", $source, $usedMatches);
    $used = array_unique($usedMatches[1]);

    expect($used)->not->toBeEmpty();
    foreach ($used as $code) {
        expect($documented)->toContain($code);
    }
});
