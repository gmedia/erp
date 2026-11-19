<?php

use App\Models\SocialLoginTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('social-auth');

test('cleanup command deletes only old expired tickets', function () {
    $user = User::factory()->create();

    $old = SocialLoginTicket::create([
        'token_hash' => str_repeat('a', 64),
        'user_id' => $user->id,
        'state_nonce' => 'old-nonce',
        'provider' => 'google',
        'account_type' => 'workspace',
        'ip_hash' => str_repeat('1', 64),
        'user_agent_hash' => str_repeat('2', 64),
        'expires_at' => now()->subDays(2),
    ]);

    $recent = SocialLoginTicket::create([
        'token_hash' => str_repeat('b', 64),
        'user_id' => $user->id,
        'state_nonce' => 'recent-nonce',
        'provider' => 'google',
        'account_type' => 'workspace',
        'ip_hash' => str_repeat('3', 64),
        'user_agent_hash' => str_repeat('4', 64),
        'expires_at' => now()->addMinute(),
    ]);

    $this->artisan('social:cleanup-tickets')
        ->expectsOutputToContain('Deleted 1 expired social login ticket(s).')
        ->assertSuccessful();

    expect(SocialLoginTicket::find($old->id))->toBeNull()
        ->and(SocialLoginTicket::find($recent->id))->not->toBeNull();
});
