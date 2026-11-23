import { useAuth } from '@/contexts/auth-context';
import axios from '@/lib/axios';
import {
    enablePushNotifications,
    isPushSupported,
} from '@/lib/push-notifications';
import { isAxiosError } from 'axios';
import { useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { toast } from 'sonner';

import {
    SettingsRow,
    SettingsSaveButton,
    SettingsSection,
} from '@/components/common/settings';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
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
}: Readonly<{
    mustVerifyEmail?: boolean;
    status?: string;
}>) {
    const { user, refreshAuth } = useAuth();
    const [searchParams] = useSearchParams();
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [pushBusy, setPushBusy] = useState(false);
    const verificationStatus =
        status ?? searchParams.get('status') ?? undefined;

    const handleSubmit = async (
        e: Readonly<React.SubmitEvent<HTMLFormElement>>,
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
                <SettingsSection
                    title="Profile information"
                    description="Update your name and email address"
                    actions={
                        <SettingsSaveButton
                            formId="profile-form"
                            processing={processing}
                            recentlySuccessful={recentlySuccessful}
                            dataTest="update-profile-button"
                        />
                    }
                >
                    <form id="profile-form" onSubmit={handleSubmit}>
                        <SettingsRow
                            label="Name"
                            htmlFor="name"
                            description="Shown across the app and in approvals."
                            error={errors.name}
                        >
                            <Input
                                id="name"
                                defaultValue={user?.name ?? ''}
                                name="name"
                                required
                                autoComplete="name"
                                placeholder="Full name"
                            />
                        </SettingsRow>

                        <SettingsRow
                            label="Email address"
                            htmlFor="email"
                            description="Used to sign in and receive notifications."
                            error={errors.email}
                        >
                            <Input
                                id="email"
                                type="email"
                                defaultValue={user?.email ?? ''}
                                name="email"
                                required
                                autoComplete="username"
                                placeholder="Email address"
                            />
                        </SettingsRow>

                        {mustVerifyEmail &&
                            user?.email_verified_at === null && (
                                <div>
                                    <p className="text-sm text-muted-foreground">
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
                                            className="text-foreground underline decoration-muted-foreground/40 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current!"
                                        >
                                            Click here to resend the
                                            verification email.
                                        </button>
                                    </p>

                                    {verificationStatus ===
                                        'verification-link-sent' && (
                                        <div className="mt-2 text-sm font-medium text-success">
                                            A new verification link has been
                                            sent to your email address.
                                        </div>
                                    )}
                                </div>
                            )}
                    </form>
                </SettingsSection>

                {isPushSupported() && (
                    <div className="space-y-6">
                        <HeadingSmall
                            title="Notifications"
                            description="Enable push notifications on this device."
                        />
                        <Button
                            type="button"
                            variant="outline"
                            disabled={pushBusy}
                            onClick={async () => {
                                setPushBusy(true);

                                try {
                                    const result =
                                        await enablePushNotifications();

                                    if (result === 'denied') {
                                        toast.error(
                                            'Notification permission denied.',
                                        );
                                    }
                                } catch {
                                    toast.error(
                                        'Failed to enable notifications.',
                                    );
                                } finally {
                                    setPushBusy(false);
                                }
                            }}
                        >
                            {pushBusy
                                ? 'Enabling notifications...'
                                : 'Enable notifications'}
                        </Button>
                    </div>
                )}
            </SettingsLayout>
        </AppLayout>
    );
}
