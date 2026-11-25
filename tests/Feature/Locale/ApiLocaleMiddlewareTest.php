<?php

use App\Http\Middleware\SetLocaleMiddleware;
use App\Models\Account;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\Supplier;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('locale');

beforeEach(function () {
    $user = createTestUserWithPermissions(['ap_payment', 'ap_payment.create']);
    Sanctum::actingAs($user, ['*']);
});

test('api request with Accept-Language id returns indonesian locale and translations', function () {
    $response = getJson('/api/me', ['Accept-Language' => 'id']);

    $response->assertOk()
        ->assertJsonPath('locale', 'id')
        ->assertJsonPath('translations.language.switch', 'Bahasa');
});

test('api request with Accept-Language en returns english locale', function () {
    getJson('/api/me', ['Accept-Language' => 'en'])
        ->assertOk()
        ->assertJsonPath('locale', 'en')
        ->assertJsonPath('translations.language.switch', 'Language');
});

test('api request with X-Locale header takes effect', function () {
    getJson('/api/me', ['X-Locale' => 'id'])
        ->assertOk()
        ->assertJsonPath('locale', 'id');
});

test('unknown locale falls back to default locale', function () {
    getJson('/api/me', ['Accept-Language' => 'fr-FR'])
        ->assertOk()
        ->assertJsonPath('locale', config('app.locale'));
});

test('missing locale header falls back to default locale', function () {
    getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('locale', config('app.locale'));
});

test('accept-language with quality values picks the highest supported locale', function () {
    getJson('/api/me', ['Accept-Language' => 'fr;q=0.9, id;q=0.8, en;q=0.1'])
        ->assertOk()
        ->assertJsonPath('locale', 'id');
});

test('validation message is translated to the requested locale', function () {
    $supplier = Supplier::factory()->create();
    $otherSupplier = Supplier::factory()->create();
    $bankAccount = Account::factory()->create(['type' => 'asset']);
    $foreignBill = SupplierBill::factory()->confirmed()->create(['supplier_id' => $otherSupplier->id]);

    $payload = [
        'supplier_id' => $supplier->id,
        'branch_id' => Branch::factory()->create()->id,
        'fiscal_year_id' => FiscalYear::factory()->create()->id,
        'payment_date' => '2026-03-05',
        'payment_method' => 'bank_transfer',
        'bank_account_id' => $bankAccount->id,
        'currency' => 'IDR',
        'total_amount' => 1000,
        'status' => 'draft',
        'allocations' => [
            ['supplier_bill_id' => $foreignBill->getKey(), 'allocated_amount' => 1000],
        ],
    ];

    $response = postJson('/api/ap-payments', $payload, ['Accept-Language' => 'id']);

    $response->assertUnprocessable();
    $messages = $response->json('errors')['allocations.0.supplier_bill_id'] ?? null;

    expect($messages)->toBeArray();
    expect($messages)->not->toBeEmpty();
    expect($messages[0])->toBe(__('ap_payment.bill_supplier_mismatch', [], 'id'));
});

test('export request runs under the requested locale', function () {
    postJson('/api/supplier-bills/export', [], ['Accept-Language' => 'id']);

    expect(app()->getLocale())->toBe('id');
});

test('set locale middleware is registered for the api group', function () {
    $api = app('router')->getMiddlewareGroups()['api'] ?? [];

    expect($api)->toContain(SetLocaleMiddleware::class);
});
