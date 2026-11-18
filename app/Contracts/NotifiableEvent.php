<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface NotifiableEvent
{
    /**
     * Stable key registered in config('notifications.events').
     */
    public function eventKey(): string;

    /**
     * Recipients of this event.
     *
     * @return Collection<int, User>
     */
    public function audience(): Collection;

    /**
     * Channels to use (database, mail, ...).
     *
     * @return list<string>
     */
    public function channels(): array;

    /**
     * Notification payload. Must include a human-readable 'title' and 'body'.
     *
     * @return array<string, mixed>
     */
    public function payload(): array;

    /**
     * SPA path the recipient is taken to when opening the notification.
     */
    public function targetUrl(): string;

    /**
     * Stable identity of the entity this event is about, used for dedupe.
     */
    public function dedupeIdentity(): string;
}
