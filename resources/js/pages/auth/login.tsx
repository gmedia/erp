import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAuth } from '@/contexts/auth-context';
import AuthLayout from '@/layouts/auth-layout';
import axiosInstance from '@/lib/axios';
import axios from 'axios';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { useLocation, useNavigate } from 'react-router-dom';

interface LoginProps {
    readonly status?: string;
    readonly canResetPassword?: boolean;
}

function getFormValue(formData: FormData, key: string): string {
    const value = formData.get(key);
    return typeof value === 'string' ? value : '';
}

export default function Login({ status, canResetPassword = true }: LoginProps) {
    const { login } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();

    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const formData = new FormData(e.currentTarget);
        const data = {
            email: getFormValue(formData, 'email'),
            password: getFormValue(formData, 'password'),
            remember: formData.get('remember') === 'on',
        };

        try {
            // We use token based auth via API
            const response = await axiosInstance.post('/api/login', data);

            // AuthController returns: { token: "...", user: {...} }
            const payload = response.data;

            login(payload.token, payload);

            // Redirect to intended location or dashboard
            const from = location.state?.from?.pathname || '/dashboard';
            navigate(from, { replace: true });
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                if (error.response?.status === 422) {
                    setErrors(error.response.data.errors || {});
                } else if (error.response?.status === 401) {
                    setErrors({
                        email: 'These credentials do not match our records.',
                    });
                } else {
                    setErrors({
                        email: 'An error occurred during login. Please try again.',
                    });
                }
            } else {
                setErrors({
                    email: 'An unexpected error occurred.',
                });
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <AuthLayout
            title="Log in to your account"
            description="Enter your email and password below to log in"
        >
            <Helmet>
                <title>Log in - {import.meta.env.VITE_APP_NAME || 'ERP'}</title>
            </Helmet>

            <form onSubmit={handleSubmit} className="flex flex-col gap-6">
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autoFocus
                            autoComplete="email"
                            placeholder="email@example.com"
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <div className="flex items-center">
                            <Label htmlFor="password">Password</Label>
                            {canResetPassword && (
                                <TextLink
                                    to="/forgot-password"
                                    className="ml-auto text-sm"
                                >
                                    Forgot password?
                                </TextLink>
                            )}
                        </div>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autoComplete="current-password"
                            placeholder="Password"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" />
                        <Label htmlFor="remember">Remember me</Label>
                    </div>

                    <Button
                        type="submit"
                        className="mt-4 w-full"
                        disabled={processing}
                        data-test="login-button"
                    >
                        {processing && (
                            <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                        )}
                        Log in
                    </Button>
                </div>
            </form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </AuthLayout>
    );
}
