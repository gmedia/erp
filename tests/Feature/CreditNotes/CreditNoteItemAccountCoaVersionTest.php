<?php

use App\Models\Account;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\Customer;
use App\Models\FiscalYear;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

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

function creditNotePayloadForAccountCoa(int $accountId): array
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

test('store rejects an active account that belongs to an archived COA version', function () {
    $archivedVersion = CoaVersion::factory()->create(['status' => 'archived']);
    $account = Account::factory()->create([
        'coa_version_id' => $archivedVersion->id,
        'is_active' => true,
    ]);

    postJson('/api/credit-notes', creditNotePayloadForAccountCoa($account->id))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.account_id']);
});
