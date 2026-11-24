<?php

use App\Models\ArReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('ar-receipts');

beforeEach(function () {
    $user = createTestUserWithPermissions([
        'ar_receipt',
        'ar_receipt.edit',
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('put status transition for an ar receipt follows the whitelist', function (
    string $from,
    string $to,
    int $expected,
) {
    $receipt = ArReceipt::factory()->create(['status' => $from]);

    putJson('/api/ar-receipts/' . $receipt->id, [
        'status' => $to,
    ])->assertStatus($expected);

    if ($expected === 422) {
        expect($receipt->refresh()->status)->toBe($from);
    }
})->with([
    'draft to reconciled is rejected' => ['draft', 'reconciled', 422],
    'cancelled to reconciled is rejected' => ['cancelled', 'reconciled', 422],
    'void to reconciled is rejected' => ['void', 'reconciled', 422],
    'confirmed to reconciled is allowed' => ['confirmed', 'reconciled', 200],
    'reconciled resent unchanged is allowed' => ['reconciled', 'reconciled', 200],
]);

test('ar receipt status rejection message is translated for en and id', function (string $locale, string $expected) {
    app()->setLocale($locale);
    $receipt = ArReceipt::factory()->create(['status' => 'draft']);

    $response = putJson('/api/ar-receipts/' . $receipt->id, [
        'status' => 'reconciled',
    ])->assertStatus(422);

    expect($response->json('errors.status.0'))->toBe($expected);
})->with([
    ['en', 'Cannot change status from draft to reconciled.'],
    ['id', 'Tidak dapat mengubah status dari draft ke reconciled.'],
]);
