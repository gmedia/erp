<?php

use App\Actions\SocialAuth\CreateSocialLoginTicketAction;
use App\Models\SocialLoginTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('social-auth');

function ticketRequest(?string $ip = '127.0.0.1', ?string $ua = 'PHPUnit'): Request
{
    return Request::create('/api/social/exchange', 'POST', [], [], [], [
        'REMOTE_ADDR' => $ip,
        'HTTP_USER_AGENT' => $ua,
    ]);
}

function issueTicket(User $user, string $nonce = 'nonce-1'): string
{
    return app(CreateSocialLoginTicketAction::class)->execute(
        $user, ticketRequest(), $nonce, 'google', 'workspace'
    );
}

test('ticket is hashed and not stored in plain text', function () {
    $user = User::factory()->create();
    $token = issueTicket($user);

    $ticket = SocialLoginTicket::query()->firstOrFail();

    expect($ticket->token_hash)->toBe(hash('sha256', $token))
        ->and($ticket->token_hash)->not->toBe($token)
        ->and($ticket->provider)->toBe('google')
        ->and($ticket->account_type)->toBe('workspace');
});

test('ticket is consumed atomically and cannot be replayed', function () {
    $user = User::factory()->create();
    $token = issueTicket($user);

    expect(SocialLoginTicket::consume($token, ticketRequest()))->not->toBeNull()
        ->and(SocialLoginTicket::consume($token, ticketRequest()))->toBeNull();
});

test('ticket from different user agent is rejected', function () {
    $user = User::factory()->create();
    $token = issueTicket($user);

    expect(SocialLoginTicket::consume($token, ticketRequest('127.0.0.1', 'OtherAgent')))->toBeNull()
        ->and(SocialLoginTicket::consume($token, ticketRequest()))->not->toBeNull();
});

test('ticket from different ip is rejected', function () {
    $user = User::factory()->create();
    $token = issueTicket($user);

    expect(SocialLoginTicket::consume($token, ticketRequest('10.0.0.9', 'PHPUnit')))->toBeNull()
        ->and(SocialLoginTicket::consume($token, ticketRequest()))->not->toBeNull();
});

test('expired ticket is rejected', function () {
    $user = User::factory()->create();
    $token = issueTicket($user);
    SocialLoginTicket::query()->update(['expires_at' => now()->subMinute()]);

    expect(SocialLoginTicket::consume($token, ticketRequest()))->toBeNull();
});

test('exchange endpoint rejects request without ticket header', function () {
    $response = $this->postJson('/api/social/exchange', []);

    $response->assertStatus(401)->assertJson(['error_code' => 'ticket_invalid']);
});

test('exchange endpoint returns sanctum token for valid ticket', function () {
    $user = User::factory()->create();
    $token = issueTicket($user);

    $response = $this->withHeader('X-Social-Ticket-Token', $token)
        ->postJson('/api/social/exchange', [], ['User-Agent' => 'PHPUnit']);

    $response->assertOk()->assertJsonStructure(['token', 'user']);
});
