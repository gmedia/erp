import axiosInstance from '@/lib/axios';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { Helmet } from 'react-helmet-async';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { mapFirstValidationErrors } from '@/utils/errorHandling';

export default function ForgotPassword() {
    const [status, setStatus] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});
        setStatus(null);

        const formData = new FormData(e.currentTarget);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await axiosInstance.post(
                '/api/forgot-password',
                data,
            );
            setStatus(response.data.status);
        } catch (error: unknown) {
            const formattedErrors = mapFirstValidationErrors(error);

            if (Object.keys(formattedErrors).length > 0) {
                setErrors(formattedErrors);
            } else {
                setErrors({
                    email: 'An error occurred. Please try again later.',
                });
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <AuthLayout
            title="Forgot password"
            description="Enter your email to receive a password reset link"
        >
            <Helmet>
                <title>
                    Forgot password - {import.meta.env.VITE_APP_NAME || 'ERP'}
                </title>
            </Helmet>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <div className="space-y-6">
                <form onSubmit={handleSubmit}>
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autoComplete="off"
                            autoFocus
                            placeholder="email@example.com"
                        />

                        <InputError message={errors.email} />
                    </div>

                    <div className="my-6 flex items-center justify-start">
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                            data-test="email-password-reset-link-button"
                        >
                            {processing && (
                                <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                            )}
                            Email password reset link
                        </Button>
                    </div>
                </form>

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>Or, return to</span>
                    <TextLink to="/login">log in</TextLink>
                </div>
            </div>
        </AuthLayout>
    );
}
