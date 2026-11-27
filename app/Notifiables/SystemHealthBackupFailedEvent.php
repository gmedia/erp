<?php

namespace App\Notifiables;

use App\Contracts\NotifiableEvent;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Backup terjadwal gagal. Dedupe per kejadian (waktu kegagalan), sehingga satu kegagalan
 * hanya menghasilkan satu notifikasi per penerima dan channel.
 */
class SystemHealthBackupFailedEvent implements NotifiableEvent
{
    /**
     * @param  Collection<int, User>  $audience
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly string $failureId,
        private readonly Collection $audience,
        private readonly array $payload,
    ) {}

    public function eventKey(): string
    {
        return 'system_health.backup.failed';
    }

    public function audience(): Collection
    {
        return $this->audience;
    }

    public function channels(): array
    {
        return ['database', 'mail'];
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function targetUrl(): string
    {
        return '/system-health';
    }

    public function dedupeIdentity(): string
    {
        return $this->failureId;
    }
}
