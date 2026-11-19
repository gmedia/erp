<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification event registry
    |--------------------------------------------------------------------------
    |
    | event_key => ['label' => ..., 'channels' => [...]].
    | Modules reference an event key instead of embedding channel logic.
    | Changes to this registry are picked up by NotificationService.
    |
    */

    'events' => [
        'approval.request.created' => [
            'label' => 'New approval request waiting for you',
            'channels' => ['database', 'mail'],
        ],
        'approval.request.approved' => [
            'label' => 'Approval request approved',
            'channels' => ['database', 'mail'],
        ],
        'approval.request.rejected' => [
            'label' => 'Approval request rejected',
            'channels' => ['database', 'mail'],
        ],
        'vehicle_loan.pickup.due' => [
            'label' => 'Vehicle pickup scheduled',
            'channels' => ['database'],
        ],
        'vehicle_loan.return.due' => [
            'label' => 'Vehicle return due',
            'channels' => ['database'],
        ],
    ],
];
