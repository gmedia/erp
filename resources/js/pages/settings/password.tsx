import axios from '@/lib/axios';
import { LoaderCircle } from 'lucide-react';
import { useRef, useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { toast } from 'sonner';

import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';

import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Password settings',
        href: '/settings/password',
    },
];

export default function Password() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});
        setRecentlySuccessful(false);

        const formData = new FormData(e.currentTarget);
        const data = Object.fromEntries(formData.entries());

        try {
            await axios.put('/user/password', data);
            setRecentlySuccessful(true);

            // Clear passwords
            if (currentPasswordInput.current)
                currentPasswordInput.current.value = '';
            if (passwordInput.current) passwordInput.current.value = '';
            const confirmInput = document.getElementById(
                'password_confirmation',
            ) as HTMLInputElement;
            if (confirmInput) confirmInput.value = '';

            toast.success('Password updated successfully');

            setTimeout(() => setRecentlySuccessful(false), 2000);
        } catch (error: any) {
            if (error.response?.status === 422) {
                const returnedErrors = error.response.data.errors || {};
                const formattedErrors: Record<string, string> = {};
                Object.keys(returnedErrors).forEach((key) => {
                    formattedErrors[key] = Array.isArray(returnedErrors[key])
                        ? returnedErrors[key][0]
                        : returnedErrors[key];
                });
                setErrors(formattedErrors);

                if (formattedErrors.password) {
                    passwordInput.current?.focus();
                } else if (formattedErrors.current_password) {
                    currentPasswordInput.current?.focus();
                }
            } else {
                toast.error('An error occurred. Please try again later.');
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>
                    Password settings - {import.meta.env.VITE_APP_NAME || 'ERP'}
                </title>
            </Helmet>

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Update password"
                        description="Ensure your account is using a long, random password to stay secure"
                    />

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="current_password">
                                Current password
                            </Label>

                            <Input
                                id="current_password"
                                ref={currentPasswordInput}
                                name="current_password"
                                type="password"
                                className="mt-1 block w-full"
                                autoComplete="current-password"
                                placeholder="Current password"
                                required
                            />

                            <InputError message={errors.current_password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">New password</Label>

                            <Input
                                id="password"
                                ref={passwordInput}
                                name="password"
                                type="password"
                                className="mt-1 block w-full"
                                autoComplete="new-password"
                                placeholder="New password"
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
                                name="password_confirmation"
                                type="password"
                                className="mt-1 block w-full"
                                autoComplete="new-password"
                                placeholder="Confirm password"
                                required
                            />

                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        <div className="flex items-center gap-4">
                            <Button
                                type="submit"
                                disabled={processing}
                                data-test="update-password-button"
                            >
                                {processing && (
                                    <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                )}
                                Save password
                            </Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out duration-300"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out duration-300"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">
                                    Saved
                                </p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
