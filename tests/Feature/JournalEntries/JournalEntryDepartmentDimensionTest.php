<?php

use App\Models\Account;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('journal-entries');

describe('Department dimension (#114 fase 1)', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'journal_entry',
            'journal_entry.create',
            'journal_entry.edit',
        ]);
        actingAs($user);

        FiscalYear::factory()->create([
            'status' => 'open',
            'start_date' => '2023-01-01',
            'end_date' => '2023-12-31',
        ]);
    });

    test('migration adds nullable department_id to accounting dimension tables', function () {
        foreach ([
            'journal_entry_lines',
            'customer_invoices',
            'supplier_bills',
            'credit_notes',
        ] as $table) {
            expect(Schema::hasColumn($table, 'department_id'))->toBeTrue();
        }

        expect(Schema::hasColumn('journal_entry_lines', 'department_id'))->toBeTrue();
    });

    test('line factory defaults department to null and withDepartment sets one', function () {
        $plain = JournalEntryLine::factory()->create();
        expect($plain->department_id)->toBeNull();

        $tagged = JournalEntryLine::factory()->withDepartment()->create();
        expect($tagged->department_id)->not->toBeNull()
            ->and($tagged->department)->toBeInstanceOf(Department::class);
    });

    test('deleting a department nulls the line reference instead of deleting the line', function () {
        $department = Department::factory()->create();
        $line = JournalEntryLine::factory()->create(['department_id' => $department->id]);

        $department->delete();

        $line->refresh();
        expect($line->department_id)->toBeNull()
            ->and(JournalEntryLine::query()->whereKey($line->id)->exists())->toBeTrue();
    });

    test('store saves department per line and keeps lines without department null', function () {
        $department = Department::factory()->create();
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $response = postJson('/api/journal-entries', [
            'entry_date' => '2023-01-15',
            'description' => 'Beban operasional',
            'lines' => [
                [
                    'account_id' => $account1->id,
                    'department_id' => $department->id,
                    'debit' => 750,
                    'credit' => 0,
                ],
                [
                    'account_id' => $account2->id,
                    'debit' => 0,
                    'credit' => 750,
                ],
            ],
        ]);

        $response->assertCreated();

        $entry = JournalEntry::query()->latest('id')->firstOrFail();
        $lines = $entry->lines()->orderBy('id')->get();

        expect($lines[0]->department_id)->toBe($department->id)
            ->and($lines[1]->department_id)->toBeNull();

        assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $entry->id,
            'account_id' => $account1->id,
            'department_id' => $department->id,
        ]);
    });

    test('store rejects a department id that does not exist', function () {
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $response = postJson('/api/journal-entries', [
            'entry_date' => '2023-01-15',
            'description' => 'Departemen tidak valid',
            'lines' => [
                [
                    'account_id' => $account1->id,
                    'department_id' => 999999,
                    'debit' => 100,
                    'credit' => 0,
                ],
                [
                    'account_id' => $account2->id,
                    'debit' => 0,
                    'credit' => 100,
                ],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lines.0.department_id']);
    });

    test('update replaces the department of each line', function () {
        $oldDepartment = Department::factory()->create();
        $newDepartment = Department::factory()->create();
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $entry = JournalEntry::factory()->create([
            'entry_date' => '2023-02-10',
            'status' => 'draft',
        ]);
        $entry->lines()->create([
            'account_id' => $account1->id,
            'department_id' => $oldDepartment->id,
            'debit' => 300,
            'credit' => 0,
        ]);
        $entry->lines()->create([
            'account_id' => $account2->id,
            'debit' => 0,
            'credit' => 300,
        ]);

        $response = putJson("/api/journal-entries/{$entry->id}", [
            'entry_date' => '2023-02-10',
            'description' => 'Ubah departemen',
            'lines' => [
                [
                    'account_id' => $account1->id,
                    'department_id' => $newDepartment->id,
                    'debit' => 300,
                    'credit' => 0,
                ],
                [
                    'account_id' => $account2->id,
                    'debit' => 0,
                    'credit' => 300,
                ],
            ],
        ]);

        $response->assertOk();

        $debitLine = $entry->lines()->where('account_id', $account1->id)->firstOrFail();
        $creditLine = $entry->lines()->where('account_id', $account2->id)->firstOrFail();

        expect($debitLine->department_id)->toBe($newDepartment->id)
            ->and($creditLine->department_id)->toBeNull();
    });

    test('show returns department id and name per line', function () {
        $department = Department::factory()->create(['name' => 'Keuangan']);
        $account = Account::factory()->create();

        $entry = JournalEntry::factory()->create(['status' => 'draft']);
        $entry->lines()->create([
            'account_id' => $account->id,
            'department_id' => $department->id,
            'debit' => 100,
            'credit' => 0,
        ]);

        $response = getJson("/api/journal-entries/{$entry->id}");

        $response->assertOk()
            ->assertJsonPath('data.lines.0.department_id', $department->id)
            ->assertJsonPath('data.lines.0.department_name', 'Keuangan');
    });
});
