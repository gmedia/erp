<?php

use App\Actions\SecurityLogs\DetectFailedLoginBurstAction;
use App\Actions\SecurityLogs\PruneSecurityLogsAction;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\SecurityLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('security-logs');

describe('prune', function () {
    it('deletes logs older than the retention window and keeps recent ones', function () {
        $old = SecurityLog::factory()->create(['created_at' => now()->subDays(400)]);
        $edge = SecurityLog::factory()->create(['created_at' => now()->subDays(364)]);

        $deleted = app(PruneSecurityLogsAction::class)->execute();

        expect($deleted)->toBe(1)
            ->and(SecurityLog::query()->whereKey($old->id)->exists())->toBeFalse()
            ->and(SecurityLog::query()->whereKey($edge->id)->exists())->toBeTrue();
    });

    it('honours the retention setting from Pengaturan Admin', function () {
        Setting::create([
            'group' => 'security',
            'key' => 'security_log_retention_days',
            'value' => '30',
            'type' => 'integer',
        ]);

        $log = SecurityLog::factory()->create(['created_at' => now()->subDays(45)]);

        app(PruneSecurityLogsAction::class)->execute();

        expect(SecurityLog::query()->whereKey($log->id)->exists())->toBeFalse();
    });

    it('is runnable through the scheduled artisan command', function () {
        SecurityLog::factory()->create(['created_at' => now()->subDays(400)]);

        $this->artisan('security-logs:prune')->assertSuccessful();

        expect(SecurityLog::query()->count())->toBe(0);
    });
});

describe('failed login burst alert', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $this->admin->id]);
        $employee->permissions()->sync([
            Permission::firstOrCreate(['name' => 'security_log'], ['display_name' => 'Security Log'])->id,
        ]);
    });

    it('notifies admins once per subject (account and IP) within the window', function () {
        $email = 'target@example.com';

        SecurityLog::factory()->failed($email)
            ->count(DetectFailedLoginBurstAction::DEFAULT_THRESHOLD)
            ->create(['ip_address' => '10.0.0.5']);

        app(DetectFailedLoginBurstAction::class)->execute($email, '10.0.0.5');
        app(DetectFailedLoginBurstAction::class)->execute($email, '10.0.0.5');

        // Per subject, NotificationService writes one in-app row (database) and one mail
        // reservation row (mail). Count the database channel to assert the in-app contract.
        $inApp = DatabaseNotification::query()
            ->where('notifiable_id', $this->admin->id)
            ->where('data->event_key', 'security.failed_login_burst')
            ->where('data->channel', null)
            ->count();

        // Two subjects (account + IP), each deduped on the repeat call.
        expect($inApp)->toBe(2);
    });

    it('does not notify below the threshold', function () {
        $email = 'quiet@example.com';

        SecurityLog::factory()->failed($email)->count(DetectFailedLoginBurstAction::DEFAULT_THRESHOLD - 1)->create();

        app(DetectFailedLoginBurstAction::class)->execute($email, null);

        expect(DatabaseNotification::query()->where('data->event_key', 'security.failed_login_burst')->exists())
            ->toBeFalse();
    });

    it('ignores failures older than the window', function () {
        $email = 'old@example.com';

        SecurityLog::factory()->failed($email)
            ->count(DetectFailedLoginBurstAction::DEFAULT_THRESHOLD)
            ->create(['created_at' => now()->subMinutes(DetectFailedLoginBurstAction::DEFAULT_WINDOW_MINUTES + 5)]);

        app(DetectFailedLoginBurstAction::class)->execute($email, null);

        expect(DatabaseNotification::query()->where('data->event_key', 'security.failed_login_burst')->exists())
            ->toBeFalse();
    });

    it('uses the threshold from settings', function () {
        Setting::create([
            'group' => 'security',
            'key' => 'security_log_failed_login_threshold',
            'value' => '2',
            'type' => 'integer',
        ]);

        $email = 'strict@example.com';
        SecurityLog::factory()->failed($email)->count(2)->create();

        app(DetectFailedLoginBurstAction::class)->execute($email, null);

        expect(DatabaseNotification::query()->where('data->event_key', 'security.failed_login_burst')->exists())
            ->toBeTrue();
    });
});

describe('index and export access', function () {
    it('denies the index without the security_log permission', function () {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->getJson('/api/security-logs')->assertForbidden();
    });

    it('lists logs for a user with security_log', function () {
        $viewer = createTestUserWithPermissions(['security_log']);
        SecurityLog::factory()->count(2)->create();

        Sanctum::actingAs($viewer, ['*']);

        $this->getJson('/api/security-logs')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters by event', function () {
        $viewer = createTestUserWithPermissions(['security_log']);
        SecurityLog::factory()->failed('a@example.com')->create();
        SecurityLog::factory()->create(['event' => SecurityLog::EVENT_LOGOUT]);

        Sanctum::actingAs($viewer, ['*']);

        $this->getJson('/api/security-logs?event=login_failed')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('denies export without security_log.export', function () {
        $viewer = createTestUserWithPermissions(['security_log']);

        Sanctum::actingAs($viewer, ['*']);

        $this->postJson('/api/security-logs/export')->assertForbidden();
    });

    it('exports logs when the user holds security_log.export', function () {
        $viewer = createTestUserWithPermissions(['security_log', 'security_log.export']);
        SecurityLog::factory()->count(2)->create();

        Sanctum::actingAs($viewer, ['*']);

        $response = $this->postJson('/api/security-logs/export', ['event' => 'login_success']);

        $response->assertOk();
    });
});
