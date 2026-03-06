import { Helmet } from 'react-helmet-async';
import { useNavigate } from 'react-router-dom';
import { useState } from 'react';
import axios from '@/lib/axios';
import { LoaderCircle } from 'lucide-react';
import { toast } from 'sonner';
import { useAuth } from '@/contexts/auth-context';

import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

export default function VerifyEmail({ status: initialStatus }: { status?: string }) {
    const { logout } = useAuth();
    const navigate = useNavigate();
    const [status, setStatus] = useState(initialStatus);
    const [processing, setProcessing] = useState(false);

    const handleResend = async (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        try {
            await axios.post('/email/verification-notification');
            setStatus('verification-link-sent');
            toast.success('Verification link sent');
        } catch (error) {
            toast.error('Failed to send verification link.');
        } finally {
            setProcessing(false);
        }
    };

    const handleLogout = async (e: React.MouseEvent) => {
        e.preventDefault();
        try {
            await axios.post('/api/v1/logout');
            logout();
            navigate('/login');
        } catch (error) {
            toast.error('Failed to log out.');
        }
    };

    return (
        <AuthLayout
            title="Verify email"
            description="Please verify your email address by clicking on the link we just emailed to you."
        >
            <Helmet><title>Email verification - {import.meta.env.VITE_APP_NAME || 'ERP'}</title></Helmet>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    A new verification link has been sent to the email address
                    you provided during registration.
                </div>
            )}

            <form onSubmit={handleResend} className="space-y-6 text-center">
                <Button type="submit" disabled={processing} variant="secondary">
                    {processing && (
                        <LoaderCircle className="h-4 w-4 mr-2 animate-spin" />
                    )}
                    Resend verification email
                </Button>

                <button
                    onClick={handleLogout}
                    className="mx-auto block text-sm underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                >
                    Log out
                </button>
            </form>
        </AuthLayout>
    );
}
