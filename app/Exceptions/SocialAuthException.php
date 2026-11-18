<?php

namespace App\Exceptions;

use RuntimeException;

class SocialAuthException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $status = 400,
        ?string $message = null,
    ) {
        parent::__construct($message ?? $errorCode);
    }

    public static function invalidState(): self
    {
        return new self('invalid_state', 400);
    }

    public static function emailUnverified(): self
    {
        return new self('email_unverified', 403);
    }

    public static function personalAccountsDisabled(): self
    {
        return new self('personal_accounts_disabled', 403);
    }

    public static function domainNotAllowed(): self
    {
        return new self('domain_not_allowed', 403);
    }

    public static function tenantNotAllowed(): self
    {
        return new self('tenant_not_allowed', 403);
    }

    public static function accountNotRegistered(): self
    {
        return new self('account_not_registered', 403);
    }

    public static function invalidTicket(): self
    {
        return new self('ticket_invalid', 401);
    }

    public static function twoFactorRequired(): self
    {
        return new self('two_factor_required', 403);
    }
}
