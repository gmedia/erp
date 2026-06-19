<?php

use App\Models\Account;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('journal-entries');

/**
 * @param  array<string>  $permissions
 */
function makeJournalUserInBranch(?int $branchId, array $permissions): User
{
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'branch_id' => $branchId,
        'department_id' => null,
        'position_id' => null,
    ]);

    $ids = [];
    foreach ($permissions as $name) {
        $ids[] = Permission::firstOrCreate(
            ['name' => $name],
            ['display_name' => ucwords(str_replace(['.', '-'], ' ', $name))],
        )->id;
    }
    $employee->permissions()->sync($ids);

    return $user;
}

/**
 * @return array{0: Account, 1: Account}
 */
function makeBalancedJournalLines(): array
{
    FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    return [Account::factory()->create(), Account::factory()->create()];
}

/**
 * @param  array{0: Account, 1: Account}  $accounts
 * @return array<string, mixed>
 */
function balancedJournalPayload(array $accounts, ?int $branchId = null): array
{
    $payload = [
        'entry_date' => '2026-03-15',
        'description' => 'Manual entry',
        'lines' => [
            ['account_id' => $accounts[0]->id, 'debit' => 1000, 'credit' => 0],
            ['account_id' => $accounts[1]->id, 'debit' => 0, 'credit' => 1000],
        ],
    ];

    if ($branchId !== null) {
        $payload['branch_id'] = $branchId;
    }

    return $payload;
}

describe('Manual journal entry branch attribution gate', function () {
    test('view_all_branches user keeps the requested branch_id', function () {
        $accounts = makeBalancedJournalLines();
        $branch = Branch::factory()->create();
        $user = makeJournalUserInBranch(null, ['journal_entry', 'journal_entry.create', 'view_all_branches']);
        actingAs($user);

        $response = postJson('/api/journal-entries', balancedJournalPayload($accounts, $branch->id));

        $response->assertCreated();
        expect(JournalEntry::first()->branch_id)->toBe($branch->id);
    });

    test('view_all_branches user may post a company-wide (null) entry', function () {
        $accounts = makeBalancedJournalLines();
        $user = makeJournalUserInBranch(null, ['journal_entry', 'journal_entry.create', 'view_all_branches']);
        actingAs($user);

        $response = postJson('/api/journal-entries', balancedJournalPayload($accounts));

        $response->assertCreated();
        expect(JournalEntry::first()->branch_id)->toBeNull();
    });

    test('branch-scoped employee is forced to own branch, ignoring requested branch_id', function () {
        $accounts = makeBalancedJournalLines();
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $user = makeJournalUserInBranch($ownBranch->id, ['journal_entry', 'journal_entry.create']);
        actingAs($user);

        $response = postJson('/api/journal-entries', balancedJournalPayload($accounts, $otherBranch->id));

        $response->assertCreated();
        expect(JournalEntry::first()->branch_id)->toBe($ownBranch->id);
    });

    test('branch-scoped employee posting without branch_id still lands on own branch', function () {
        $accounts = makeBalancedJournalLines();
        $ownBranch = Branch::factory()->create();
        $user = makeJournalUserInBranch($ownBranch->id, ['journal_entry', 'journal_entry.create']);
        actingAs($user);

        $response = postJson('/api/journal-entries', balancedJournalPayload($accounts));

        $response->assertCreated();
        expect(JournalEntry::first()->branch_id)->toBe($ownBranch->id);
    });

    test('legacy admin (no employee branch, no permission) stays unscoped', function () {
        $accounts = makeBalancedJournalLines();
        $branch = Branch::factory()->create();
        $user = makeJournalUserInBranch(null, ['journal_entry', 'journal_entry.create']);
        actingAs($user);

        $response = postJson('/api/journal-entries', balancedJournalPayload($accounts, $branch->id));

        $response->assertCreated();
        expect(JournalEntry::first()->branch_id)->toBe($branch->id);
    });

    test('rejects a branch_id that does not exist', function () {
        $accounts = makeBalancedJournalLines();
        $user = makeJournalUserInBranch(null, ['journal_entry', 'journal_entry.create', 'view_all_branches']);
        actingAs($user);

        $response = postJson('/api/journal-entries', balancedJournalPayload($accounts, 999999));

        $response->assertStatus(422)->assertJsonValidationErrors(['branch_id']);
    });

    test('store response exposes the resolved branch', function () {
        $accounts = makeBalancedJournalLines();
        $branch = Branch::factory()->create();
        $user = makeJournalUserInBranch(null, ['journal_entry', 'journal_entry.create', 'view_all_branches']);
        actingAs($user);

        $response = postJson('/api/journal-entries', balancedJournalPayload($accounts, $branch->id));

        $response->assertCreated()
            ->assertJsonPath('data.branch.id', $branch->id)
            ->assertJsonPath('data.branch.name', $branch->name);
    });
});
