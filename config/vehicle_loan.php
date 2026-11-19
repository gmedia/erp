<?php

use App\Models\VehicleLoan;

return [
    /*
    |--------------------------------------------------------------------------
    | Vehicle Loan — public base URL
    |--------------------------------------------------------------------------
    |
    | Deep links in email notifications and printed QR codes are generated from
    | the existing frontend URL setting. No production URL is hardcoded here.
    |
    */

    'public_base_url' => config('app.frontend_url', config('app.url')),

    /*
    |--------------------------------------------------------------------------
    | QR paths (appended to public_base_url)
    |--------------------------------------------------------------------------
    */

    'qr_paths' => [
        'request' => '/vehicle-loans/request',
        'pickup' => '/vehicle-loans/pickup',
        'return' => '/vehicle-loans/return',
        'security' => '/vehicle-loans/security',
    ],

    /*
    |--------------------------------------------------------------------------
    | Approval flow
    |--------------------------------------------------------------------------
    |
    | TriggerApprovalAction::resolveFlow() matches approvable_type against
    | $entity->getMorphClass(). Without a morph map this is the FQCN.
    |
    */

    'approval_flow_code' => env('VEHICLE_LOAN_APPROVAL_FLOW_CODE', 'vehicle-loan.default'),
    'approvable_type' => VehicleLoan::class,

    /*
    |--------------------------------------------------------------------------
    | Vehicle asset categories
    |--------------------------------------------------------------------------
    |
    | Comma-separated asset category IDs, configured per tenant by an admin.
    | Never assume a fixed ID such as 1.
    |
    */

    'vehicle_asset_category_ids' => array_values(array_filter(
        array_map('intval', explode(',', (string) env('VEHICLE_ASSET_CATEGORY_IDS', ''))),
    )),

    /*
    |--------------------------------------------------------------------------
    | Photo uploads
    |--------------------------------------------------------------------------
    |
    | SIM/selfie photos are sensitive: keep them on a private disk and serve
    | them through a signed, authorized controller. Do NOT use the 'public'
    | disk (it is world-readable via the /storage symlink).
    |
    */

    'photo_disk' => env('VEHICLE_LOAN_PHOTO_DISK', 'local'),
    'photo_max_kb' => (int) env('VEHICLE_LOAN_PHOTO_MAX_KB', 5120),
    'photo_mimes' => ['jpg', 'jpeg', 'png'],
    'photo_retention_days' => (int) env('VEHICLE_LOAN_PHOTO_RETENTION_DAYS', 365),
    'draft_expiry_days' => (int) env('VEHICLE_LOAN_DRAFT_EXPIRY_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Permission names (managed by MenuSeeder / permission UI)
    |--------------------------------------------------------------------------
    */

    'permissions' => [
        'view' => 'vehicle_loan',
        'request' => 'vehicle_loan.request',
        'pickup' => 'vehicle_loan.pickup',
        'return' => 'vehicle_loan.return',
        'security' => 'vehicle_loan.security',
        'report' => 'vehicle_loan.report',
        'master' => 'vehicle_loan.master',
    ],
];
