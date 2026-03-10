import axiosInstance from '@/lib/axios';
import axios from 'axios';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { toast } from 'sonner';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

interface ResetPasswordProps {
    token?: string;
    email?: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const resolvedToken = token ?? searchParams.get('token') ?? '';
    const resolvedEmail = email ?? searchParams.get('email') ?? '';

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const formData = new FormData(e.currentTarget);
        const data = Object.fromEntries(formData.entries());
        // Append hidden fields
        data.token = resolvedToken;
        data.email = resolvedEmail;

        try {
            const response = await axiosInstance.post('/api/reset-password', data);
            toast.success(
                response.data.status || 'Password has been successfully reset.',
            );
            navigate('/login', { replace: true });
        } catch (error: unknown) {
            if (axios.isAxiosError(error) && error.response?.status === 422) {
                const returnedErrors = error.response.data.errors || {};
                const formattedErrors: Record<string, string> = {};
                Object.keys(returnedErrors).forEach((key) => {
                    formattedErrors[key] = Array.isArray(returnedErrors[key])
                        ? returnedErrors[key][0]
                        : returnedErrors[key];
                });
                setErrors(formattedErrors);
            } else {
                toast.error('An error occurred. Please try again later.');
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <AuthLayout
            title="Reset password"
            description="Please enter your new password below"
        >
            <Helmet>
                <title>
                    Reset password - {import.meta.env.VITE_APP_NAME || 'ERP'}
                </title>
            </Helmet>

            <form onSubmit={handleSubmit} className="grid gap-6">
                <div className="grid gap-2">
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autoComplete="email"
                        defaultValue={resolvedEmail}
                        className="mt-1 block w-full"
                        readOnly
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        autoComplete="new-password"
                        className="mt-1 block w-full"
                        autoFocus
                        placeholder="Password"
                        required
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">
                        Confirm password
                    </Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        autoComplete="new-password"
                        className="mt-1 block w-full"
                        placeholder="Confirm password"
                        required
                    />
                    <InputError
                        message={errors.password_confirmation}
                        className="mt-2"
                    />
                </div>

                <Button
                    type="submit"
                    className="mt-4 w-full"
                    disabled={processing}
                    data-test="reset-password-button"
                >
                    {processing && (
                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                    )}
                    Reset password
                </Button>
            </form>
        </AuthLayout>
    );
}
