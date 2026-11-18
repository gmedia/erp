<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Social Login — Static Defaults
    |--------------------------------------------------------------------------
    |
    | Values here are seed defaults and technical constants only. Runtime
    | policy (allowed domains/tenants, enabled toggles) is read from the
    | `settings` table via SocialLoginPolicy so administrators can change it
    | from the UI without a deploy. Never put organization-specific domains,
    | tenant IDs, or branding in this file.
    |
    */

    'defaults' => [
        'enabled' => true,
        'jit_provisioning' => false,
        'auto_link_by_email' => false,
        'allow_personal' => false,
        'allowed_domains' => [],
        'allowed_tenants' => [],
        'password_login_enabled' => true,
    ],

    'microsoft' => [
        // 'common' accepts both MSA and M365 at the Identity Platform level;
        // allowed tenants from `settings` still decide who may sign in.
        'tenant' => env('MICROSOFT_TENANT_ID', 'common'),
        'consumers_tenant_id' => '9188040d-6c67-4c5b-b112-36a304b66dad',
    ],

    'state_ttl_seconds' => 300,
    'ticket_ttl_seconds' => 60,

    'rate_limit' => [
        'redirect' => '10,1',
        'callback' => '30,1',
        'exchange' => '20,1',
    ],
];
