<?php

use App\Models\User;
use App\Models\VehicleLoan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('locale');

beforeEach(function () {
    $this->user = createTestUserWithPermissions([
        'vehicle_loan',
        'vehicle_loan.request',
        'approval_delegation',
        'approval_delegation.create',
    ]);
    Sanctum::actingAs($this->user, ['*']);
});

test('vehicle loan cancel message defaults to english without locale header', function () {
    $loan = VehicleLoan::factory()->create([
        'requester_id' => $this->user->id,
        'status' => 'draft',
    ]);

    deleteJson("/api/vehicle-loans/{$loan->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Loan request cancelled.');
});

test('vehicle loan cancel message is indonesian with X-Locale id', function () {
    $loan = VehicleLoan::factory()->create([
        'requester_id' => $this->user->id,
        'status' => 'draft',
    ]);

    deleteJson("/api/vehicle-loans/{$loan->id}", [], ['X-Locale' => 'id'])
        ->assertOk()
        ->assertJsonPath('message', 'Permintaan Pinjaman Kendaraan dibatalkan.');
});

test('vehicle loan update error is indonesian with X-Locale id', function () {
    $loan = VehicleLoan::factory()->create([
        'requester_id' => $this->user->id,
        'status' => 'approved',
    ]);

    putJson("/api/vehicle-loans/{$loan->id}", ['purpose' => 'Perubahan'], ['X-Locale' => 'id'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Hanya Pinjaman Kendaraan berstatus draf yang dapat diubah.');
});

test('approval delegation created message is indonesian with X-Locale id', function () {
    $delegator = User::factory()->create();
    $delegate = User::factory()->create();

    postJson('/api/approval-delegations', [
        'delegator_user_id' => $delegator->id,
        'delegate_user_id' => $delegate->id,
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-10',
        'reason' => 'Locale test',
        'is_active' => true,
    ], ['X-Locale' => 'id'])
        ->assertCreated()
        ->assertJsonPath('message', 'Delegasi Persetujuan berhasil dibuat');
});

test('notification read message defaults to english without locale header', function () {
    $notification = notificationForCurrentUser($this->user);

    postJson("/api/notifications/{$notification->id}/read")
        ->assertOk()
        ->assertJsonPath('message', 'Notification marked as read.');
});

test('notification read message is indonesian with X-Locale id', function () {
    $notification = notificationForCurrentUser($this->user);

    postJson("/api/notifications/{$notification->id}/read", [], ['X-Locale' => 'id'])
        ->assertOk()
        ->assertJsonPath('message', 'Notifikasi ditandai sudah dibaca.');
});

test('notification not found error is indonesian with X-Locale id', function () {
    postJson('/api/notifications/' . Str::uuid() . '/read', [], ['X-Locale' => 'id'])
        ->assertNotFound()
        ->assertJsonPath('message', 'Notifikasi tidak ditemukan.');
});

test('backend api message keys exist in both english and indonesian locales', function () {
    $en = json_decode(File::get(lang_path('en.json')), true, 512, JSON_THROW_ON_ERROR);
    $id = json_decode(File::get(lang_path('id.json')), true, 512, JSON_THROW_ON_ERROR);

    $prefixes = [
        'vehicle_loan_api.', 'my_approval_api.', 'entity_state_api.',
        'notification_api.', 'approval_delegation_api.', 'entity_approval_history_api.',
    ];

    $keys = array_filter(
        array_keys($en),
        fn (string $key) => array_any($prefixes, fn (string $prefix) => str_starts_with($key, $prefix)),
    );

    expect($keys)->not->toBeEmpty();
    foreach ($keys as $key) {
        expect(array_key_exists($key, $id))->toBeTrue("{$key} missing in id.json");
        expect($id[$key])->not->toBe($en[$key], "{$key} not translated");
    }
});

function notificationForCurrentUser(User $user): DatabaseNotification
{
    return DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'App\\Notifications\\LocaleTest',
        'notifiable_type' => $user->getMorphClass(),
        'notifiable_id' => $user->id,
        'dedupe_key' => 'locale-test:' . Str::uuid() . ':' . $user->id . ':database',
        'data' => ['message' => 'locale'],
    ]);
}
