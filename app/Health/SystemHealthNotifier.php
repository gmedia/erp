<?php

namespace App\Health;

use App\Models\Permission;
use App\Models\User;
use App\Notifiables\SystemHealthStatusEvent;
use App\Notifications\GenericAppNotification;
use App\Services\NotificationService;
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
     * @return array{name: string, label: string, from: string|null, to: string, message: string}|null
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

        return [
            'name' => $result->check->getName(),
            'label' => $result->check->getLabel(),
            'from' => $from,
            'to' => $to,
            'message' => $result->getNotificationMessage() ?: $result->getShortSummary(),
        ];
    }

    /**
     * @param  array{name: string, label: string, from: string|null, to: string, message: string}  $transition
     * @param  Collection<int, User>  $audience
     */
    private function dispatchTransition(array $transition, string $runId, Collection $audience): void
    {
        $recovered = $transition['to'] === 'ok';
        $title = $recovered
            ? "Pulih: {$transition['label']}"
            : "Peringatan: {$transition['label']} ({$transition['to']})";

        $payload = [
            'title' => $title,
            'body' => $transition['message'],
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
