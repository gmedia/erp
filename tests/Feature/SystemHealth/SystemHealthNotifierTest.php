<?php

use App\Health\SystemHealthRunner;
use App\Health\SystemHealthThresholds;
use App\Models\Setting;
use App\Notifications\GenericAppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Spatie\Health\Facades\Health;

uses(RefreshDatabase::class)->group('system-health');

/**
 * Cek palsu dengan status yang bisa diatur dari test.
 */
function fakeHealthCheck(string $name, string $status): Check
{
    return new class($name, $status) extends Check
    {
        public function __construct(private string $checkName, private string $checkStatus)
        {
            // Intentionally empty. Dependencies are assigned via constructor promotion.
        }

        public function getName(): string
        {
            return $this->checkName;
        }

        public function run(): Result
        {
            $result = Result::make()->shortSummary($this->checkStatus);

            return match ($this->checkStatus) {
                'failed' => $result->failed('komponen gagal'),
                'warning' => $result->warning('komponen perlu perhatian'),
                default => $result->ok('komponen sehat'),
            };
        }
    };
}

function setFakeChecks(array $checks): void
{
    Health::checks($checks);
}

/**
 * Notifikasi in-app (channel database). Baris email memakai data.channel = mail dan tidak ikut.
 *
 * @return Collection<int, DatabaseNotification>
 */
function inAppNotificationsFor(int $userId): Collection
{
    return DatabaseNotification::query()
        ->where('notifiable_id', $userId)
        ->where('type', GenericAppNotification::class)
        ->get()
        ->filter(fn (DatabaseNotification $n): bool => ($n->data['channel'] ?? null) === null)
        ->values();
}

beforeEach(function () {
    Mail::fake();
});

test('runner sends in-app notification once when a component turns failed', function () {
    $permissionUser = createTestUserWithPermissions(['system_health']);

    Health::clearChecks();
    setFakeChecks([fakeHealthCheck('disk', 'ok')]);
    app(SystemHealthRunner::class)->run();

    Health::clearChecks();
    setFakeChecks([fakeHealthCheck('disk', 'failed')]);
    app(SystemHealthRunner::class)->run();

    expect(inAppNotificationsFor($permissionUser->id))->toHaveCount(1);
});

test('runner does not notify again while the status stays the same', function () {
    $permissionUser = createTestUserWithPermissions(['system_health']);

    Health::clearChecks();
    setFakeChecks([fakeHealthCheck('disk', 'failed')]);
    app(SystemHealthRunner::class)->run();

    Health::clearChecks();
    setFakeChecks([fakeHealthCheck('disk', 'failed')]);
    app(SystemHealthRunner::class)->run();

    Health::clearChecks();
    setFakeChecks([fakeHealthCheck('disk', 'failed')]);
    app(SystemHealthRunner::class)->run();

    // Hanya transisi pertama (ok/kosong -> failed) yang memberi notifikasi in-app.
    expect(inAppNotificationsFor($permissionUser->id))->toHaveCount(1);
});

test('runner notifies recovery when a failed component returns to ok', function () {
    // Judul notifikasi mengikuti locale default perusahaan (id: "Pulih: ...").
    config(['app.locale' => 'id']);
    $permissionUser = createTestUserWithPermissions(['system_health']);

    Health::clearChecks();
    setFakeChecks([fakeHealthCheck('disk', 'failed')]);
    app(SystemHealthRunner::class)->run();

    Health::clearChecks();
    setFakeChecks([fakeHealthCheck('disk', 'ok')]);
    app(SystemHealthRunner::class)->run();

    $titles = inAppNotificationsFor($permissionUser->id)
        ->map(fn ($n) => $n->data['title'] ?? '')
        ->all();

    expect($titles)->toHaveCount(2);
    expect(collect($titles)->contains(fn ($t) => str_starts_with((string) $t, 'Pulih')))->toBeTrue();
});

test('runner sends alert email to configured admin address on transition', function () {
    createTestUserWithPermissions(['system_health']);
    Setting::set(SystemHealthThresholds::ALERT_EMAIL, 'it-admin@example.com');

    Health::clearChecks();
    setFakeChecks([fakeHealthCheck('disk', 'warning')]);
    app(SystemHealthRunner::class)->run();

    Mail::assertQueued(GenericAppNotification::class, function ($mail) {
        return $mail->recipientEmail === 'it-admin@example.com';
    });
});

test('runner sends no email when no alert address is configured', function () {
    createTestUserWithPermissions(['system_health']);

    Health::clearChecks();
    setFakeChecks([fakeHealthCheck('disk', 'failed')]);
    app(SystemHealthRunner::class)->run();

    expect(SystemHealthThresholds::alertEmail())->toBeNull();
    Mail::assertNotQueued(GenericAppNotification::class, function ($mail) {
        return $mail->recipientEmail === 'it-admin@example.com';
    });
});
