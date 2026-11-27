<?php

namespace App\Health;

use App\Models\Permission;
use App\Models\User;
use App\Notifiables\SystemHealthStatusEvent;
use App\Notifications\GenericAppNotification;
use App\Services\NotificationService;
use App\Support\LocaleJsonTranslator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Health\Checks\Result;
use Spatie\Health\Enums\Status;
use Spatie\Health\ResultStores\EloquentHealthResultStore;
use Spatie\Health\ResultStores\StoredCheckResults\StoredCheckResult;
use Throwable;

/**
 * Mengirim notifikasi SEKALI per perubahan status sebuah komponen:
 * memburuk (ok -> warning/failed/crashed) dan pulih (-> ok). Transisi yang tidak berubah
 * tidak mengirim apa pun. Penerima: pengguna ber-permission system_health (in-app) dan
 * alamat admin IT dari Pengaturan (email).
 */
/**
 * @phpstan-type Transition array{name: string, label: string, from: string|null, to: string, message: string,
 *     body: string|null}
 */
class SystemHealthNotifier
{
    public function __construct(private readonly NotificationService $notifications)
    {
        // Intentionally empty. Dependencies are assigned via promoted properties.
    }

    /**
     * @param  Collection<int, Result>  $results  Hasil cek yang baru selesai.
     */
    public function notifyTransitions(Collection $results, string $runId): void
    {
        $previous = $this->previousStatuses();

        $transitions = $results
            ->filter(fn (Result $result): bool => (string) $result->status !== 'skipped')
            ->map(fn (Result $result): ?array => $this->transitionFor($result, $previous[$result->check->getName()] ?? null))
            ->filter()
            ->values();

        if ($transitions->isEmpty()) {
            return;
        }

        $audience = $this->audience();

        foreach ($transitions as $transition) {
            $this->dispatchTransition($transition, $runId, $audience);
        }
    }

    /**
     * @return Transition|null
     */
    private function transitionFor(Result $result, ?string $previousStatus): ?array
    {
        $to = $this->normalize($result->status);
        $from = $previousStatus === null ? null : $this->normalize($previousStatus);

        if ($from === $to) {
            return null;
        }

        // Baru pertama kali tercatat dan sehat: tidak ada yang perlu diberitahu.
        if ($from === null && $to === 'ok') {
            return null;
        }

        /** @var array<string, mixed> $meta */
        $meta = (array) $result->meta;
        $code = $meta['message_code'] ?? null;
        $params = is_array($meta['message_params'] ?? null) ? $meta['message_params'] : [];

        if (! is_string($code)) {
            $resolved = SystemHealthMessage::fromPackage((string) $result->getNotificationMessage(), $meta);
            $code = $resolved['code'];
            $params = $resolved['params'];
        }

        return [
            'name' => $result->check->getName(),
            'label' => $result->check->getLabel(),
            'from' => $from,
            'to' => $to,
            'message' => $result->getNotificationMessage() ?: $result->getShortSummary(),
            'body' => $code === null ? null : LocaleJsonTranslator::get($code, $params, $this->recipientLocale()),
        ];
    }

    /**
     * Locale notifikasi: belum ada preferensi bahasa per pengguna di database, jadi memakai
     * locale default aplikasi/perusahaan (APP_LOCALE), dibatasi ke locale yang didukung.
     */
    private function recipientLocale(): string
    {
        $default = (string) config('app.locale', 'en');
        $available = (array) config('app.available_locales', ['en', 'id']);

        return in_array($default, $available, true) ? $default : 'en';
    }

    /**
     * @param  Transition  $transition
     * @param  Collection<int, User>  $audience
     */
    private function dispatchTransition(array $transition, string $runId, Collection $audience): void
    {
        $locale = $this->recipientLocale();
        $recovered = $transition['to'] === 'ok';
        $title = $recovered
            ? LocaleJsonTranslator::get('system_health.notice.recovered', ['label' => $transition['label']], $locale)
            : LocaleJsonTranslator::get('system_health.notice.degraded', [
                'label' => $transition['label'],
                'status' => $transition['to'],
            ], $locale);

        $payload = [
            'title' => $title,
            'body' => $transition['body'] ?? $transition['message'],
            'check' => $transition['name'],
            'from' => $transition['from'],
            'to' => $transition['to'],
        ];

        try {
            $this->notifications->send(new SystemHealthStatusEvent(
                checkName: $transition['name'],
                transitionId: $runId,
                audience: $audience,
                payload: $payload,
            ));

            $this->sendAlertEmail($payload);
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('System health notification failed.', ['check' => $transition['name']]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sendAlertEmail(array $payload): void
    {
        $email = SystemHealthThresholds::alertEmail();

        if ($email === null) {
            return;
        }

        // Email dikirim lewat antrean (GenericAppNotification implements ShouldQueue).
        Mail::send(new GenericAppNotification(
            eventKey: 'system_health.status.changed',
            payload: $payload,
            targetUrl: '/system-health',
            recipientEmail: $email,
        ));
    }

    /**
     * @return Collection<int, User>
     */
    private function audience(): Collection
    {
        $permission = Permission::query()->where('name', 'system_health')->first();

        if ($permission === null) {
            return collect();
        }

        return User::query()
            ->whereHas('employee.permissions', fn ($query) => $query->whereKey($permission->id))
            ->get();
    }

    /**
     * @return array<string, string>
     */
    private function previousStatuses(): array
    {
        $latest = (new EloquentHealthResultStore)->latestResults();

        if ($latest === null) {
            return [];
        }

        return $latest->storedCheckResults
            ->mapWithKeys(fn (StoredCheckResult $stored): array => [$stored->name => (string) $stored->status])
            ->all();
    }

    private function normalize(Status|string $status): string
    {
        $value = $status instanceof Status ? (string) $status : $status;

        return $value === 'crashed' ? 'failed' : $value;
    }
}
