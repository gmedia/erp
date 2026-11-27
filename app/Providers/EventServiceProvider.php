<?php

namespace App\Providers;

use App\Events\ApprovalRequestApproved;
use App\Events\ApprovalRequestCreated;
use App\Events\ApprovalRequestRejected;
use App\Listeners\DispatchGenericNotification;
use App\Listeners\MirrorPurchaseOrderApprovalStatus;
use App\Listeners\MirrorPurchaseRequestApprovalStatus;
use App\Listeners\MirrorVehicleLoanApprovalStatus;
use App\Listeners\SecurityLogAuthListener;
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
    /**
     * @var list<class-string>
     */
    protected $subscribe = [
        SecurityLogAuthListener::class,
    ];

    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ApprovalRequestCreated::class => [
            DispatchGenericNotification::class,
        ],
        ApprovalRequestApproved::class => [
            DispatchGenericNotification::class,
            MirrorVehicleLoanApprovalStatus::class,
            MirrorPurchaseRequestApprovalStatus::class,
            MirrorPurchaseOrderApprovalStatus::class,
        ],
        ApprovalRequestRejected::class => [
            DispatchGenericNotification::class,
            MirrorVehicleLoanApprovalStatus::class,
            MirrorPurchaseRequestApprovalStatus::class,
            MirrorPurchaseOrderApprovalStatus::class,
        ],
    ];

    // Intentionally empty. Behavior is inherited from the base provider.
}
