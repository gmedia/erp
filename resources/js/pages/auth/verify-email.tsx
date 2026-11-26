import { useAuth } from '@/contexts/auth-context';
import axios from '@/lib/axios';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { useToastTranslator } from '@/utils/i18nFallback';

interface VerifyEmailProps {
    readonly status?: string;
}

export default function VerifyEmail({
    status: initialStatus,
}: VerifyEmailProps) {
    const { logout } = useAuth();
    const navigate = useNavigate();
    const tToast = useToastTranslator('auth_toast');
    const [status, setStatus] = useState(initialStatus);
    const [processing, setProcessing] = useState(false);

    const handleResend = async (e: React.SubmitEvent<HTMLFormElement>) => {
        e.preventDefault();
        setProcessing(true);

        try {
            await axios.post('/email/verification-notification');
            setStatus('verification-link-sent');
            toast.success(
                tToast('verification_link_sent', 'Verification link sent'),
            );
        } catch {
            toast.error(
                tToast(
                    'verification_link_failed',
                    'Failed to send verification link.',
                ),
            );
        } finally {
            setProcessing(false);
        }
    };

    const handleLogout = async (e: React.MouseEvent) => {
        e.preventDefault();
        try {
            await axios.post('/api/logout');
            void logout();
            void navigate('/login');
        } catch {
            toast.error(tToast('logout_failed', 'Failed to log out.'));
        }
    };

    return (
        <AuthLayout
            title="Verify email"
            description="Please verify your email address by clicking on the link we just emailed to you."
        >
            <Helmet>
                <title>
                    Email verification -{' '}
                    {import.meta.env.VITE_APP_NAME || 'ERP'}
                </title>
            </Helmet>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-success">
                    A new verification link has been sent to the email address
                    you provided during registration.
                </div>
            )}

            <form onSubmit={handleResend} className="space-y-6 text-center">
                <Button type="submit" disabled={processing} variant="secondary">
                    {processing && (
                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                    )}
                    Resend verification email
                </Button>

                <button
                    type="button"
                    onClick={handleLogout}
                    className="mx-auto block text-sm underline decoration-muted-foreground/40 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current!"
                >
                    Log out
                </button>
            </form>
        </AuthLayout>
    );
}
