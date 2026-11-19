<?php

namespace App\Notifiables;

use App\Contracts\NotifiableEvent;
use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Support\Collection;

class ApprovalEvent implements NotifiableEvent
{
    /**
     * @param  Collection<int, User>  $audience
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly string $eventKey,
        private readonly ApprovalRequest $approvalRequest,
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
        return ['database', 'mail', 'push'];
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
        return 'approval-request-' . $this->approvalRequest->id . '-' . $this->eventKey;
    }
}
