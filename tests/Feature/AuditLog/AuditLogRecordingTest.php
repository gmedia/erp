<?php

use App\Actions\AuditLog\PruneAuditLogAction;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class)->group('audit-log');

describe('recording financial changes', function () {
    it('records old and new values only for changed attributes', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $setting = Setting::factory()->create([
            'key' => 'company_name',
            'group' => 'company',
            'value' => 'PT Lama',
            'type' => 'string',
        ]);

        $setting->update(['value' => 'PT Baru']);

        $activity = Activity::query()
            ->where('subject_type', Setting::class)
            ->where('subject_id', $setting->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->log_name)->toBe('finance')
            ->and($activity->causer_id)->toBe($user->id)
            ->and($activity->attribute_changes['old']['value'])->toBe('PT Lama')
            ->and($activity->attribute_changes['attributes']['value'])->toBe('PT Baru')
            ->and($activity->attribute_changes['attributes'])->not->toHaveKey('group');
    });

    it('does not record an activity when nothing changed', function () {
        $setting = Setting::factory()->create([
            'key' => 'currency',
            'group' => 'company',
            'value' => 'IDR',
            'type' => 'string',
        ]);

        $before = Activity::query()->where('subject_type', Setting::class)->count();

        $setting->update(['value' => 'IDR']);

        expect(Activity::query()->where('subject_type', Setting::class)->count())->toBe($before);
    });

    it('records account (chart of accounts) changes', function () {
        $account = Account::factory()->create(['name' => 'Kas Lama']);

        $account->update(['name' => 'Kas Baru']);

        $activity = Activity::query()
            ->where('subject_type', Account::class)
            ->where('subject_id', $account->id)
            ->where('event', 'updated')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->attribute_changes['old']['name'])->toBe('Kas Lama')
            ->and($activity->attribute_changes['attributes']['name'])->toBe('Kas Baru');
    });

    it('records a journal entry change so it appears in the audit list', function () {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $entry = JournalEntry::factory()->create([
            'description' => 'Deskripsi Lama',
            'status' => 'draft',
        ]);

        $entry->update(['description' => 'Deskripsi Baru']);

        $activity = Activity::query()
            ->where('subject_type', JournalEntry::class)
            ->where('subject_id', $entry->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->causer_id)->toBe($user->id)
            ->and($activity->attribute_changes['old']['description'])->toBe('Deskripsi Lama')
            ->and($activity->attribute_changes['attributes']['description'])->toBe('Deskripsi Baru');
    });
});

describe('sensitive attributes', function () {
    it('never stores password or remember token in the audit log', function () {
        $user = User::factory()->create(['password' => bcrypt('rahasia-lama-123')]);

        $user->update([
            'name' => 'Nama Baru',
            'password' => bcrypt('rahasia-baru-456'),
            'remember_token' => 'token-rahasia',
        ]);

        $activity = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $payload = json_encode([$activity?->attribute_changes, $activity?->properties]);

        expect($activity)->not->toBeNull()
            ->and($activity->attribute_changes['attributes']['name'])->toBe('Nama Baru')
            ->and($payload)->not->toContain('password')
            ->and($payload)->not->toContain('remember_token')
            ->and($payload)->not->toContain('token-rahasia')
            ->and($payload)->not->toContain('rahasia-baru-456');
    });

    it('hides the value of settings whose key looks like a secret', function () {
        $setting = Setting::factory()->create([
            'key' => 'smtp_password',
            'group' => 'mail',
            'value' => 'nilai-lama-rahasia',
            'type' => 'string',
        ]);

        $setting->update(['value' => 'nilai-baru-rahasia', 'type' => 'json']);

        $activity = Activity::query()
            ->where('subject_type', Setting::class)
            ->where('subject_id', $setting->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $payload = json_encode([$activity?->attribute_changes, $activity?->properties]);

        expect($activity)->not->toBeNull()
            ->and($activity->attribute_changes['attributes'])->toHaveKey('type')
            ->and($activity->attribute_changes['attributes'])->not->toHaveKey('value')
            ->and($activity->attribute_changes['old'] ?? [])->not->toHaveKey('value')
            ->and($payload)->not->toContain('nilai-lama-rahasia')
            ->and($payload)->not->toContain('nilai-baru-rahasia');
    });
});

describe('pruning', function () {
    it('deletes audit logs older than the retention window and keeps recent ones', function () {
        Setting::factory()->create([
            'key' => PruneAuditLogAction::SETTING_RETENTION_DAYS,
            'group' => 'audit',
            'value' => '30',
            'type' => 'integer',
        ]);

        $old = Activity::query()->create([
            'log_name' => 'finance',
            'description' => 'lama',
            'event' => 'updated',
        ]);
        $old->forceFill(['created_at' => now()->subDays(40)])->saveQuietly();

        $recent = Activity::query()->create([
            'log_name' => 'finance',
            'description' => 'baru',
            'event' => 'updated',
        ]);

        $deleted = app(PruneAuditLogAction::class)->execute(Carbon::now());

        expect($deleted)->toBe(1)
            ->and(Activity::query()->find($old->id))->toBeNull()
            ->and(Activity::query()->find($recent->id))->not->toBeNull();
    });

    it('falls back to 365 days when the retention setting is invalid', function () {
        Setting::factory()->create([
            'key' => PruneAuditLogAction::SETTING_RETENTION_DAYS,
            'group' => 'audit',
            'value' => '0',
            'type' => 'integer',
        ]);

        expect(PruneAuditLogAction::retentionDays())->toBe(365);
    });
});
