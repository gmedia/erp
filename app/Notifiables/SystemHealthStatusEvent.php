<?php

namespace App\Notifiables;

use App\Contracts\NotifiableEvent;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Perubahan status satu komponen kesehatan sistem (memburuk atau pulih).
 * Dikirim sekali per transisi, dedupe oleh NotificationService.
 */
class SystemHealthStatusEvent implements NotifiableEvent
{
    /**
     * @param  Collection<int, User>  $audience
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly string $checkName,
        private readonly string $transitionId,
        private readonly Collection $audience,
        private readonly array $payload,
    ) {}

    public function eventKey(): string
    {
        return 'system_health.status.changed';
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
        return $this->checkName . '-' . $this->transitionId;
    }
}
