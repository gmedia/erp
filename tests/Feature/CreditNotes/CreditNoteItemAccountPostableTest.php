<?php

use App\Models\Account;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FiscalYear;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('credit-notes');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'credit_note',
        'credit_note.create',
        'account',
    ]);
    Sanctum::actingAs($user, ['*']);
});

function creditNotePayloadWithAccount(int $accountId): array
{
    return [
        'customer_id' => Customer::factory()->create()->id,
        'branch_id' => Branch::factory()->create()->id,
        'fiscal_year_id' => FiscalYear::factory()->create()->id,
        'credit_note_date' => '2026-03-06',
        'reason' => 'return',
        'status' => 'draft',
        'items' => [
            [
                'product_id' => Product::factory()->create()->id,
                'account_id' => $accountId,
                'description' => 'Returned item',
                'quantity' => 1,
                'unit_price' => 10000,
                'tax_percent' => 0,
            ],
        ],
    ];
}

test('store accepts an active postable account on an item', function () {
    $account = Account::factory()->inActiveCoa()->create(['is_active' => true]);

    postJson('/api/credit-notes', creditNotePayloadWithAccount($account->id))
        ->assertCreated();
});

test('store rejects an inactive account on an item', function () {
    $account = Account::factory()->inActiveCoa()->create(['is_active' => false]);

    postJson('/api/credit-notes', creditNotePayloadWithAccount($account->id))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.account_id']);
});

test('store rejects a parent account that has children on an item', function () {
    $parent = Account::factory()->inActiveCoa()->create(['is_active' => true]);
    Account::factory()->create([
        'coa_version_id' => $parent->coa_version_id,
        'parent_id' => $parent->id,
    ]);

    postJson('/api/credit-notes', creditNotePayloadWithAccount($parent->id))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.account_id']);
});

test('account index returns only postable accounts when has_children is 0', function () {
    $parent = Account::factory()->create(['is_active' => true]);
    $leaf = Account::factory()->create([
        'coa_version_id' => $parent->coa_version_id,
        'parent_id' => $parent->id,
        'is_active' => true,
    ]);

    $response = getJson("/api/accounts?coa_version_id={$parent->coa_version_id}&is_active=1&has_children=0&per_page=100")
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');

    expect($ids)->toContain($leaf->id)
        ->and($ids)->not->toContain($parent->id);
});
