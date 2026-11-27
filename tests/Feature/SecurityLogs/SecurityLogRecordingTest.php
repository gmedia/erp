<?php

use App\Actions\Employees\SyncEmployeePermissionsAction;
use App\Actions\SecurityLogs\RecordSecurityLogAction;
use App\Actions\SocialAuth\CreateSocialLoginTicketAction;
use App\Models\Permission;
use App\Models\SecurityLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('security-logs');

beforeEach(function () {
    Setting::firstOrCreate(
        ['key' => 'password_login_enabled'],
        ['group' => 'authentication', 'value' => '1', 'type' => 'boolean'],
    );
    Setting::firstOrCreate(
        ['key' => 'social_login_enabled'],
        ['group' => 'authentication', 'value' => '1', 'type' => 'boolean'],
    );
});

describe('authentication events', function () {
    it('records a successful password login with ip and short user agent', function () {
        $user = User::factory()->create(['password' => bcrypt('rahasia-kuat-123')]);

        postJson('/api/login', [
            'email' => $user->email,
            'password' => 'rahasia-kuat-123',
        ], ['User-Agent' => 'Mozilla/5.0 TestAgent'])->assertOk();

        $log = SecurityLog::query()->where('event', SecurityLog::EVENT_LOGIN_SUCCESS)->first();

        expect($log)->not->toBeNull()
            ->and($log->user_id)->toBe($user->id)
            ->and($log->ip_address)->not->toBeNull()
            ->and($log->user_agent)->toContain('TestAgent');
    });

    it('records a failed login with the attempted email and no user id', function () {
        $user = User::factory()->create(['password' => bcrypt('rahasia-kuat-123')]);

        postJson('/api/login', [
            'email' => $user->email,
            'password' => 'salah-sekali',
        ])->assertStatus(422);

        $log = SecurityLog::query()->where('event', SecurityLog::EVENT_LOGIN_FAILED)->first();

        expect($log)->not->toBeNull()
            ->and($log->user_id)->toBeNull()
            ->and($log->attempted_email)->toBe($user->email);
    });

    it('records logout for the authenticated user', function () {
        $user = User::factory()->create(['password' => bcrypt('rahasia-kuat-123')]);

        $token = postJson('/api/login', [
            'email' => $user->email,
            'password' => 'rahasia-kuat-123',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout')
            ->assertOk();

        expect(SecurityLog::query()->where('event', SecurityLog::EVENT_LOGOUT)->where('user_id', $user->id)->exists())
            ->toBeTrue();
    });

    it('records a social login when a ticket is exchanged for a token', function () {
        $user = User::factory()->create();
        $ticketRequest = Request::create('/api/social/exchange', 'POST', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'PHPUnit',
        ]);
        $ticketToken = app(CreateSocialLoginTicketAction::class)->execute(
            $user,
            $ticketRequest,
            'nonce-social-1',
            'google',
            'workspace',
        );

        // The ticket is bound to the IP and user agent it was issued for; send the same ones.
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1', 'HTTP_USER_AGENT' => 'PHPUnit'])
            ->withHeader('X-Social-Ticket-Token', $ticketToken)
            ->postJson('/api/social/exchange')
            ->assertOk();

        $log = SecurityLog::query()->where('event', SecurityLog::EVENT_LOGIN_SUCCESS)->where('user_id', $user->id)->first();

        expect($log)->not->toBeNull()
            ->and($log->metadata)->toBe(['method' => 'social']);
    });

    it('records a password reset', function () {
        $user = User::factory()->create();

        event(new PasswordReset($user));

        $exists = SecurityLog::query()
            ->where('event', SecurityLog::EVENT_PASSWORD_RESET)
            ->where('user_id', $user->id)
            ->exists();

        expect($exists)->toBeTrue();
    });

    it('never stores the password or token in any column', function () {
        $user = User::factory()->create(['password' => bcrypt('rahasia-kuat-123')]);

        postJson('/api/login', [
            'email' => $user->email,
            'password' => 'rahasia-kuat-123',
        ]);
        postJson('/api/login', [
            'email' => $user->email,
            'password' => 'salah-sekali',
        ]);

        $row = SecurityLog::query()->get()->toJson();

        expect($row)->not->toContain('rahasia-kuat-123')
            ->and($row)->not->toContain('salah-sekali');
    });
});

describe('role change', function () {
    it('records a role change when employee permissions differ', function () {
        $target = createTestUserWithPermissions(['approval_audit_trail']);
        $employee = $target->employee;

        $permissionId = Permission::firstOrCreate(
            ['name' => 'user.edit'],
            ['display_name' => 'User Edit'],
        )->id;

        (new SyncEmployeePermissionsAction)->execute($employee, [$permissionId]);

        $log = SecurityLog::query()->where('event', SecurityLog::EVENT_ROLE_CHANGED)->first();

        expect($log)->not->toBeNull()
            ->and($log->user_id)->toBe($target->id)
            ->and($log->metadata['added'])->toContain($permissionId);
    });

    it('does not record a role change when permissions are unchanged', function () {
        $target = createTestUserWithPermissions(['approval_audit_trail']);
        $employee = $target->employee;
        $currentIds = $employee->permissions()->pluck('permissions.id')->all();

        (new SyncEmployeePermissionsAction)->execute($employee, $currentIds);

        expect(SecurityLog::query()->where('event', SecurityLog::EVENT_ROLE_CHANGED)->exists())->toBeFalse();
    });
});

describe('sensitive metadata', function () {
    it('drops password-like keys passed as metadata', function () {
        $action = app(RecordSecurityLogAction::class);

        $log = $action->execute(
            SecurityLog::EVENT_LOGIN_FAILED,
            metadata: ['password' => 'x', 'Token' => 'y', 'guard' => 'web'],
        );

        expect($log->metadata)->toBe(['guard' => 'web']);
    });
});
