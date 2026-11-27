<?php

namespace App\Notifiables;

use App\Contracts\NotifiableEvent;
use App\Models\User;
use Illuminate\Support\Collection;

class SecurityLogEvent implements NotifiableEvent
{
    /**
     * @param  Collection<int, User>  $audience
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly string $eventKey,
        private readonly string $dedupeIdentity,
        private readonly Collection $audience,
        private readonly array $payload,
        private readonly string $targetUrl,
    ) {}

    public function eventKey(): string
    {
        return $this->eventKey;
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
        return $this->targetUrl;
    }

    public function dedupeIdentity(): string
    {
        return $this->dedupeIdentity;
    }
}
