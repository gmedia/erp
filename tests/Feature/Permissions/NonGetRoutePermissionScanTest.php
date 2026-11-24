<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

uses(RefreshDatabase::class)->group('route-permission-scan');

/*
 * Pemindai seluruh route API non-GET.
 * Setiap route yang mengubah data wajib membawa middleware `permission:` (izin aksi),
 * kecuali yang tercantum di $exemptions dengan alasan tertulis.
 */

/**
 * Route yang sengaja tidak memakai middleware `permission:`.
 * Tambahkan entri hanya dengan alasan yang jelas.
 *
 * @return array<string, string> "METHOD uri" => alasan
 */
function nonGetPermissionExemptions(): array
{
    $ownAccount = 'Endpoint milik akun sendiri; hanya memakai sesi login.';
    $approver = 'Alur approver; hak ditentukan penugasan approval, bukan izin modul.';

    return [
        'POST api/login' => 'Publik sebelum login.',
        'POST api/logout' => $ownAccount,
        'POST api/forgot-password' => 'Publik; reset password.',
        'POST api/reset-password' => 'Publik; reset password.',
        'PATCH api/profile' => $ownAccount,
        'DELETE api/social/accounts/{socialAccount}' => $ownAccount,
        'POST api/social/exchange' => 'Alur login sosial.',
        'POST api/social/set-password' => $ownAccount,
        'POST api/social/two-factor/verify' => 'Alur login sosial dengan 2FA.',
        'POST api/device-tokens' => $ownAccount,
        'DELETE api/device-tokens/{deviceToken}' => $ownAccount,
        'POST api/notifications/read-all' => $ownAccount,
        'POST api/notifications/{notification}/read' => $ownAccount,
        'POST api/my-approvals/{approvalRequest}/approve' => $approver,
        'POST api/my-approvals/{approvalRequest}/reject' => $approver,
        'POST api/vehicle-loans/{vehicle_loan}/review-assignment' => $approver,
        'POST api/entity-states/{entityType}/{entityId}/transition' => 'Endpoint entity-states; '
            . 'izin dicek middleware entity-state-permission.',
    ];
}

/**
 * Ambil semua route API non-GET beserta status middleware permission-nya.
 *
 * @return array<string, array{0: string, 1: bool}>
 */
function nonGetApiRoutesWithPermissionFlag(): array
{
    $rows = [];

    /** @var Route $route */
    foreach (RouteFacade::getRoutes()->getRoutes() as $route) {
        $uri = $route->uri();

        if (! str_starts_with($uri, 'api/')) {
            continue;
        }

        foreach ($route->methods() as $method) {
            if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
                continue;
            }

            $hasPermission = collect($route->gatherMiddleware())
                ->contains(fn (string $middleware) => str_starts_with($middleware, 'permission:'));

            $key = "{$method} {$uri}";
            $rows[$key] = [$key, $hasPermission];
        }
    }

    ksort($rows);

    return $rows;
}

it('memindai route API non-GET dan menemukan minimal satu route', function () {
    expect(nonGetApiRoutesWithPermissionFlag())->not->toBeEmpty();
});

it('setiap route API non-GET memakai middleware permission atau pengecualian beralasan', function () {
    $exemptions = nonGetPermissionExemptions();

    $violations = collect(nonGetApiRoutesWithPermissionFlag())
        ->filter(fn (array $row) => ! $row[1] && ! array_key_exists($row[0], $exemptions))
        ->keys()
        ->values()
        ->all();

    expect($violations)->toBe([]);
});

it('daftar pengecualian hanya berisi route yang masih ada', function () {
    $existing = array_column(nonGetApiRoutesWithPermissionFlag(), 0);

    foreach (array_keys(nonGetPermissionExemptions()) as $key) {
        expect($existing)->toContain($key);
    }
});
