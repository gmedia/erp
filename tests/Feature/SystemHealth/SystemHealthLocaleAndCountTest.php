<?php

use App\Actions\SystemHealth\GetSystemHealthStatusAction;
use App\Exports\SecurityLogExport;
use App\Health\SystemHealthMessage;
use App\Health\SystemHealthRunner;
use App\Support\LocaleJsonTranslator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;
use Spatie\Health\Facades\Health;
use Spatie\Health\Models\HealthCheckResultHistoryItem;

uses(RefreshDatabase::class)->group('system-health');

/**
 * Cek palsu dengan status tetap untuk satu transisi.
 */
function localeFakeCheck(string $name, string $status, ?Result $result = null): Check
{
    return new class($name, $status, $result) extends Check
    {
        public function __construct(
            private string $checkName,
            private string $checkStatus,
            private ?Result $preset,
        ) {
            // Intentionally empty. Dependencies are assigned via constructor promotion.
        }

        public function getName(): string
        {
            return $this->checkName;
        }

        public function run(): Result
        {
            if ($this->preset !== null) {
                return $this->preset;
            }

            $result = Result::make()->shortSummary($this->checkStatus);

            return match ($this->checkStatus) {
                'failed' => $result->failed('komponen gagal'),
                'warning' => $result->warning('komponen perlu perhatian'),
                default => $result->ok('komponen sehat'),
            };
        }
    };
}

beforeEach(function () {
    Mail::fake();
});

test('in-app health notification uses the default locale title and body', function () {
    config(['app.locale' => 'id']);
    $user = createTestUserWithPermissions(['system_health']);

    Health::clearChecks();
    Health::checks([localeFakeCheck('disk', 'ok')]);
    app(SystemHealthRunner::class)->run();

    Health::clearChecks();
    Health::checks([localeFakeCheck('disk', 'failed')]);
    app(SystemHealthRunner::class)->run();

    $notification = DatabaseNotification::query()
        ->where('notifiable_id', $user->id)
        ->get()
        ->first(fn (DatabaseNotification $n): bool => ($n->data['event_key'] ?? null) === 'system_health.status.changed'
            && ($n->data['channel'] ?? null) === null);

    expect($notification)->not->toBeNull();
    expect($notification->data['title'])->toStartWith('Peringatan:');
});

test('in-app health notification falls back to english for unsupported default locale', function () {
    config(['app.locale' => 'xx']);
    $user = createTestUserWithPermissions(['system_health']);

    Health::clearChecks();
    Health::checks([localeFakeCheck('disk', 'ok')]);
    app(SystemHealthRunner::class)->run();

    Health::clearChecks();
    Health::checks([localeFakeCheck('disk', 'failed')]);
    app(SystemHealthRunner::class)->run();

    $titles = DatabaseNotification::query()
        ->where('notifiable_id', $user->id)
        ->get()
        ->filter(fn (DatabaseNotification $n): bool => ($n->data['channel'] ?? null) === null)
        ->map(fn (DatabaseNotification $n): string => (string) ($n->data['title'] ?? ''))
        ->values()
        ->all();

    expect($titles)->toHaveCount(1);
    expect($titles[0])->toStartWith('Warning:');
});

test('queue history entry without stored meta never reports zero failed queues', function () {
    $resolved = SystemHealthMessage::fromPackage('Queue jobs running failed. Check meta for more information.', []);

    expect($resolved)->toBe([
        'code' => 'system_health.queue.failed_unknown',
        'params' => [],
    ]);
});

test('history entries reuse the stored queue meta so the count matches the card', function () {
    HealthCheckResultHistoryItem::create([
        'check_name' => 'queue',
        'check_label' => 'Antrian',
        'status' => 'failed',
        'short_summary' => 'Gagal',
        'notification_message' => 'Queue jobs running failed. Check meta for more information.',
        'meta' => ['The `default` queue did not run yet.', 'The last run of the `high` queue was more than 9 minutes ago.'],
        'ended_at' => now(),
        'batch' => (string) Str::uuid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $history = app(GetSystemHealthStatusAction::class)->execute()['history'];

    expect($history)->toHaveCount(1);
    expect($history[0]['message_code'])->toBe('system_health.queue.jobs_failed');
    expect($history[0]['message_params'])->toBe(['count' => '2']);
});

test('every system health notice key exists in both english and indonesian locales', function () {
    $decode = fn (string $file): array => json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);

    $en = $decode('en.json');
    $id = $decode('id.json');

    foreach (['system_health.notice.recovered', 'system_health.notice.degraded'] as $key) {
        expect($en)->toHaveKey($key);
        expect($id)->toHaveKey($key);
        expect($id[$key])->not->toBe($en[$key], "{$key} not translated");
    }

    expect(data_get($en, 'system_health.queue.failed_unknown'))->toBeString();
    expect(data_get($id, 'system_health.queue.failed_unknown'))->toBeString();
    expect(data_get($id, 'system_health.queue.failed_unknown'))
        ->not->toBe(data_get($en, 'system_health.queue.failed_unknown'));
});

test('nested health message codes resolve to indonesian text, not raw keys', function () {
    expect(LocaleJsonTranslator::get('system_health.queue.jobs_failed', ['count' => '2'], 'id'))
        ->toBe('Tugas antrian gagal pada 2 antrian. Periksa pekerja antrian.');
    expect(LocaleJsonTranslator::get('system_health.queue.jobs_failed', ['count' => '2'], 'en'))
        ->toStartWith('Queue jobs failed on 2');
});

test('component and scheduler terms use the indonesian vocabulary in id only', function () {
    expect(LocaleJsonTranslator::get('system_health.components.object_storage', [], 'id'))->toBe('Penyimpanan objek');
    expect(LocaleJsonTranslator::get('system_health.components.scheduler', [], 'id'))->toBe('Penjadwal');
    expect(LocaleJsonTranslator::get('system_health.components.object_storage', [], 'en'))->toBe('Object storage');
    expect(LocaleJsonTranslator::get('system_health.components.scheduler', [], 'en'))->toBe('Scheduler');
});

test('queue, container and environment wording uses indonesian terms in id', function () {
    expect(LocaleJsonTranslator::get('system_health.queue.failed_unknown', [], 'id'))
        ->toBe('Tugas antrian gagal. Periksa pekerja antrian.');
    expect(LocaleJsonTranslator::get('system_health.container.memory_ok', ['percent' => 40], 'id'))
        ->toBe('Memori kontainer terpakai 40%.');
    expect(LocaleJsonTranslator::get('system_health.production.ok', [], 'id'))
        ->toBe('Konfigurasi lingkungan sesuai.');
    expect(LocaleJsonTranslator::get('system_health.storage.ok', [], 'id'))
        ->toBe('Penyimpanan objek dapat menulis, membaca, dan menghapus berkas.');
});

test('unknown locale falls back to english for server-side translations', function () {
    expect(LocaleJsonTranslator::get('system_health.queue.failed_unknown', [], 'zz'))
        ->toBe('Queue jobs failed. Check the queue worker.');
});

test('security log export headers follow the default locale', function () {
    config(['app.locale' => 'id']);
    expect((new SecurityLogExport([]))->headings())
        ->toBe(['Tanggal', 'Peristiwa', 'Pengguna', 'Email yang dicoba', 'Alamat IP', 'User Agent', 'Metadata']);

    config(['app.locale' => 'en']);
    expect((new SecurityLogExport([]))->headings())
        ->toBe(['Date', 'Event', 'User', 'Attempted Email', 'IP Address', 'User Agent', 'Metadata']);
});

test('security log filter placeholders exist in both locales', function () {
    $decode = fn (string $file): array => json_decode(File::get(lang_path($file)), true, 512, JSON_THROW_ON_ERROR);
    $en = $decode('en.json');
    $id = $decode('id.json');

    foreach (['select_user', 'start_date', 'end_date'] as $key) {
        expect(data_get($en, "page.security_logs.{$key}"))->toBeString();
        expect(data_get($id, "page.security_logs.{$key}"))->toBeString()
            ->not->toBe(data_get($en, "page.security_logs.{$key}"));
    }
});
