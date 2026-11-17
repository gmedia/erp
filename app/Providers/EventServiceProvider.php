<?php

namespace App\Providers;

use App\Events\ApprovalRequestCreated;
use App\Listeners\SendApprovalRequestNotifications;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ApprovalRequestCreated::class => [
            SendApprovalRequestNotifications::class,
        ],
    ];

    // Intentionally empty. Behavior is inherited from the base provider.
}
