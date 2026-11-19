export type SocialProvider = 'google' | 'microsoft';

export type SocialAccountType = 'personal' | 'workspace' | 'microsoft_365';

export interface SocialRedirectResponse {
    url: string;
    state: string;
}

export interface SocialPolicyResponse {
    socialLoginEnabled: boolean;
    passwordLoginEnabled: boolean;
    personalAccountsEnabled: boolean;
    providers: SocialProvider[];
}

export type SocialAuthErrorCode =
    | 'invalid_state'
    | 'email_unverified'
    | 'personal_accounts_disabled'
    | 'domain_not_allowed'
    | 'tenant_not_allowed'
    | 'account_not_registered'
    | 'social_login_disabled'
    | 'ticket_invalid'
    | 'ticket_consumed'
    | 'two_factor_required'
    | 'invalid_two_factor_code'
    | 'last_credential'
    | 'provider_error';
