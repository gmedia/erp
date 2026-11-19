<?php

namespace App\Notifiables;

use App\Contracts\NotifiableEvent;
use App\Models\User;
use App\Models\VehicleLoan;
use Illuminate\Support\Collection;

class VehicleLoanDueEvent implements NotifiableEvent
{
    /**
     * @param  Collection<int, User>  $audience
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly VehicleLoan $loan,
        private readonly Collection $audience,
        private readonly array $payload,
    ) {}

    public function eventKey(): string
    {
        return 'vehicle_loan.return.due';
    }

    public function audience(): Collection
    {
        return $this->audience;
    }

    public function channels(): array
    {
        return ['database', 'push'];
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function targetUrl(): string
    {
        return '/vehicle-loans/' . $this->loan->id;
    }

    public function dedupeIdentity(): string
    {
        return 'vehicle-loan-' . $this->loan->id . '-return-due';
    }
}
