import { Button } from '@/components/ui/button';
import { useAuth } from '@/contexts/auth-context';
import AuthLayout from '@/layouts/auth-layout';
import axiosInstance from '@/lib/axios';
import { LoaderCircle } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { useNavigate } from 'react-router-dom';

import {
    type SocialAuthErrorCode,
    type SocialPolicyResponse,
} from '@/types/social-auth';

const ERROR_MESSAGES: Record<SocialAuthErrorCode, string> = {
    invalid_state: 'The login request expired. Please try again.',
    email_unverified: 'Please verify your email with the provider first.',
    personal_accounts_disabled: 'Personal accounts are not allowed.',
    domain_not_allowed: 'Your email domain is not allowed.',
    tenant_not_allowed: 'Your organization is not allowed.',
    account_not_registered: 'Your account is not registered. Ask an administrator.',
    social_login_disabled: 'Social login is disabled.',
    ticket_invalid: 'The login session is invalid or has expired.',
    ticket_consumed: 'This login link has already been used.',
    two_factor_required: 'Two-factor authentication is required.',
    invalid_two_factor_code: 'Invalid authentication code.',
    last_credential: 'You cannot unlink your last sign-in method.',
    provider_error: 'The login provider could not be reached.',
};

function fragmentValue(key: string): string {
    const params = new URLSearchParams(
        window.location.hash.replace(/^#/, ''),
    );

    return params.get(key) ?? '';
}

export default function SocialCallback() {
    const navigate = useNavigate();
    const { login } = useAuth();
    const [message, setMessage] = useState('Completing sign in…');
    const [failed, setFailed] = useState(false);
    const attempted = useRef(false);

    useEffect(() => {
        if (attempted.current) {
            return;
        }
        attempted.current = true;

        const error = fragmentValue('error') as SocialAuthErrorCode;
        if (error) {
            setFailed(true);
            setMessage(ERROR_MESSAGES[error] ?? ERROR_MESSAGES.provider_error);
            return;
        }

        if (fragmentValue('linked') === '1') {
            setMessage('Account linked successfully.');
            window.setTimeout(
                () => navigate('/settings/profile', { replace: true }),
                1500,
            );
            return;
        }

        const ticket = fragmentValue('ticket');
        if (!ticket) {
            setFailed(true);
            setMessage(ERROR_MESSAGES.ticket_invalid);
            return;
        }

        const intended =
            sessionStorage.getItem('social_intended') || '/dashboard';
        sessionStorage.removeItem('social_intended');

        void (async () => {
            try {
                const response = await axiosInstance.post(
                    '/api/social/exchange',
                    {},
                    { headers: { 'X-Social-Ticket-Token': ticket } },
                );
                login(response.data.token, response.data);
                navigate(intended, { replace: true });
            } catch {
                let text = ERROR_MESSAGES.ticket_invalid;
                try {
                    const policy = await axiosInstance.get<SocialPolicyResponse>(
                        '/api/social/policy',
                    );
                    if (!policy.data.socialLoginEnabled) {
                        text = ERROR_MESSAGES.social_login_disabled;
                    }
                } catch {
                    setMessage(text);
                }
                setFailed(true);
                setMessage(text);
            }
        })();
    }, [login, navigate]);

    return (
        <AuthLayout
            title={failed ? 'Sign in failed' : 'Please wait'}
            description={message}
        >
            <Helmet>
                <title>
                    Social Login - {import.meta.env.VITE_APP_NAME || 'ERP'}
                </title>
            </Helmet>
            <div className="flex flex-col items-center gap-4">
                {failed ? (
                    <Button
                        type="button"
                        onClick={() => navigate('/login', { replace: true })}
                        data-test="social-callback-back"
                    >
                        Back to login
                    </Button>
                ) : (
                    <LoaderCircle className="h-8 w-8 animate-spin text-muted-foreground" />
                )}
            </div>
        </AuthLayout>
    );
}
