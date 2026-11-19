<?php

namespace App\Actions\SocialAuth;

use App\Models\SocialLoginTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CreateSocialLoginTicketAction
{
    public function execute(
        User $user,
        Request $request,
        string $stateNonce,
        string $provider,
        string $accountType,
    ): string {
        $token = Str::random(64);
        // A state nonce may be reused when a 2FA challenge issues a fresh
        // ticket from the original login ticket. Keep the audit trail while
        // satisfying the unique index.
        $ticketNonce = hash('sha256', $stateNonce . '|' . $token);

        SocialLoginTicket::create([
            'token_hash' => SocialLoginTicket::hashToken($token),
            'user_id' => $user->id,
            'state_nonce' => $ticketNonce,
            'provider' => $provider,
            'account_type' => $accountType,
            'ip_hash' => SocialLoginTicket::hashBinding($request->ip()),
            'user_agent_hash' => SocialLoginTicket::hashBinding($request->userAgent()),
            'expires_at' => now()->addSeconds((int) config('social_login.ticket_ttl_seconds', 60)),
        ]);

        return $token;
    }
}
