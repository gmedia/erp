import { useAuth } from '@/contexts/auth-context';
import axios from '@/lib/axios';
import { isAxiosError } from 'axios';
import { useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { toast } from 'sonner';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { useSearchParams } from 'react-router-dom';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

export default function Profile({
    mustVerifyEmail = true,
    status,
}: {
    mustVerifyEmail?: boolean;
    status?: string;
}) {
    const { user, refreshAuth } = useAuth();
    const [searchParams] = useSearchParams();
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const verificationStatus =
        status ?? searchParams.get('status') ?? undefined;

    const handleSubmit = async (
        e: Readonly<React.FormEvent<HTMLFormElement>>,
    ) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});
        setRecentlySuccessful(false);

        const formData = new FormData(e.currentTarget);
        const data = Object.fromEntries(formData.entries());

        try {
            await axios.patch('/api/profile', data);
            setRecentlySuccessful(true);
            toast.success('Profile updated successfully');
            // Refresh user data by calling me endpoint
            // Refresh user data
            await refreshAuth();
            setTimeout(() => setRecentlySuccessful(false), 3000);
        } catch (error: unknown) {
            if (isAxiosError(error) && error.response?.status === 422) {
                setErrors(error.response.data.errors || {});
                toast.error('Please check the form for errors');
            } else {
                toast.error('Failed to update profile');
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>
                    Profile settings - {import.meta.env.VITE_APP_NAME || 'ERP'}
                </title>
            </Helmet>

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title="Profile information"
                        description="Update your name and email address"
                    />

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>

                            <Input
                                id="name"
                                className="mt-1 block w-full"
                                defaultValue={user?.name ?? ''}
                                name="name"
                                required
                                autoComplete="name"
                                placeholder="Full name"
                            />

                            <InputError
                                className="mt-2"
                                message={errors.name}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>

                            <Input
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                defaultValue={user?.email ?? ''}
                                name="email"
                                required
                                autoComplete="username"
                                placeholder="Email address"
                            />

                            <InputError
                                className="mt-2"
                                message={errors.email}
                            />
                        </div>

                        {mustVerifyEmail &&
                            user?.email_verified_at === null && (
                                <div>
                                    <p className="-mt-4 text-sm text-muted-foreground">
                                        Your email address is unverified.{' '}
                                        <button
                                            type="button"
                                            onClick={() =>
                                                axios
                                                    .post(
                                                        '/email/verification-notification',
                                                    )
                                                    .then(() =>
                                                        toast.success(
                                                            'Verification link sent',
                                                        ),
                                                    )
                                            }
                                            className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                        >
                                            Click here to resend the
                                            verification email.
                                        </button>
                                    </p>

                                    {verificationStatus ===
                                        'verification-link-sent' && (
                                        <div className="mt-2 text-sm font-medium text-green-600">
                                            A new verification link has been
                                            sent to your email address.
                                        </div>
                                    )}
                                </div>
                            )}

                        <div className="flex items-center gap-4">
                            <Button
                                disabled={processing}
                                data-test="update-profile-button"
                            >
                                Save
                            </Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">
                                    Saved
                                </p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
