<?php

use App\Contracts\NotifiableEvent;
use App\Contracts\PushSender;
use App\Models\ApprovalRequest;
use App\Models\DeviceToken;
use App\Models\User;
use App\Models\VehicleLoan;
use App\Notifiables\ApprovalEvent;
use App\Notifiables\VehicleLoanDueEvent;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Tests\Support\RecordingPushSender;

uses(RefreshDatabase::class)->group('notifications');

function pushChannelTestEvent(User $user): NotifiableEvent
{
    return new class($user) implements NotifiableEvent
    {
        public function __construct(private readonly User $user) {}

        public function eventKey(): string
        {
            return 'test.push';
        }

        public function audience(): Collection
        {
            return collect([$this->user]);
        }

        public function channels(): array
        {
            return ['push'];
        }

        public function payload(): array
        {
            return ['title' => 'Hello', 'body' => 'World'];
        }

        public function targetUrl(): string
        {
            return '/approvals/1';
        }

        public function dedupeIdentity(): string
        {
            return 'approval-1';
        }
    };
}

function bindRecordingPushSender(): RecordingPushSender
{
    Cache::flush();

    $sender = new RecordingPushSender;
    app()->instance(PushSender::class, $sender);

    return $sender;
}

function enableTestPushEvent(): void
{
    $events = config('notifications.events');
    $events['test.push'] = ['channels' => ['push']];
    config(['notifications.events' => $events]);
}

test('push channel sends to every registered device', function () {
    $user = User::factory()->create();
    DeviceToken::factory()->count(2)->create(['user_id' => $user->id]);

    enableTestPushEvent();
    $sender = bindRecordingPushSender();

    app(NotificationService::class)->send(pushChannelTestEvent($user));

    expect($sender->sent)->toHaveCount(2)
        ->and($sender->sent[0]['payload']['event_key'])->toBe('test.push')
        ->and($sender->sent[0]['payload']['target_url'])->toBe('/approvals/1');
});

test('push channel skips users without device tokens', function () {
    $user = User::factory()->create();

    enableTestPushEvent();
    $sender = bindRecordingPushSender();

    app(NotificationService::class)->send(pushChannelTestEvent($user));

    expect($sender->sent)->toBeEmpty();
});

test('push channel dedupes repeated events', function () {
    $user = User::factory()->create();
    DeviceToken::factory()->create(['user_id' => $user->id]);

    enableTestPushEvent();
    $sender = bindRecordingPushSender();

    $service = app(NotificationService::class);
    $service->send(pushChannelTestEvent($user));
    $service->send(pushChannelTestEvent($user));

    expect($sender->sent)->toHaveCount(1);
});

test('approval and vehicle loan events advertise the push channel', function () {
    $approval = new ApprovalEvent(
        'approval.request.created',
        new ApprovalRequest,
        collect(),
        [],
        '/approvals/1',
    );
    $loan = new VehicleLoanDueEvent(new VehicleLoan, collect(), []);

    expect($approval->channels())->toContain('push')
        ->and($loan->channels())->toContain('push');
});

test('notification config enables push for approval and vehicle loan events', function () {
    $events = config('notifications.events');

    expect($events['approval.request.created']['channels'])->toContain('push')
        ->and($events['vehicle_loan.return.due']['channels'])->toContain('push');
});
