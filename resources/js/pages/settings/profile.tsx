import { useAuth } from '@/contexts/auth-context';
import { useTranslation } from '@/contexts/i18n-context';
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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

// useTranslation requires AppI18nProvider, which wraps AppLayout. Keep every
// translated piece inside ProfileContent so it renders within that provider.
export default function Profile() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <ProfileContent />
        </AppLayout>
    );
}

function ProfileContent() {
    const { t } = useTranslation();
    const { user, refreshAuth } = useAuth();
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [pushBusy, setPushBusy] = useState(false);

    const handleSubmit = async (
        e: Readonly<React.SubmitEvent<HTMLFormElement>>,
    ) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});
        setRecentlySuccessful(false);

        const formData = new FormData(e.currentTarget);
        // Only the name is editable; email is read-only and managed by an admin.
        const data = { name: String(formData.get('name') ?? '') };

        try {
            await axios.patch('/api/profile', data);
            setRecentlySuccessful(true);
            toast.success(t('profile.update_success'));
            await refreshAuth();
            setTimeout(() => setRecentlySuccessful(false), 3000);
        } catch (error: unknown) {
            if (isAxiosError(error) && error.response?.status === 422) {
                setErrors(error.response.data.errors || {});
                toast.error(t('profile.update_validation_error'));
            } else {
                toast.error(t('profile.update_failed'));
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <>
            <Helmet>
                <title>
                    Profile settings - {import.meta.env.VITE_APP_NAME || 'ERP'}
                </title>
            </Helmet>

            <SettingsLayout>
                <SettingsSection
                    title={t('profile.title')}
                    description={t('profile.description')}
                    actions={
                        <SettingsSaveButton
                            formId="profile-form"
                            processing={processing}
                            recentlySuccessful={recentlySuccessful}
                            dataTest="update-profile-button"
                            label={t('common.save')}
                        />
                    }
                >
                    <form id="profile-form" onSubmit={handleSubmit}>
                        <SettingsRow
                            label={t('profile.name_label')}
                            htmlFor="name"
                            description={t('profile.name_description')}
                            error={errors.name}
                        >
                            <Input
                                id="name"
                                defaultValue={user?.name ?? ''}
                                name="name"
                                required
                                autoComplete="name"
                                placeholder={t('profile.name_placeholder')}
                            />
                        </SettingsRow>

                        <SettingsRow
                            label={t('profile.email_label')}
                            htmlFor="email"
                            description={t('profile.email_description')}
                        >
                            <Input
                                id="email"
                                type="email"
                                value={user?.email ?? ''}
                                name="email"
                                readOnly
                                disabled
                                autoComplete="username"
                            />
                        </SettingsRow>
                    </form>
                </SettingsSection>

                {isPushSupported() && (
                    <div className="space-y-6">
                        <HeadingSmall
                            title={t('profile.notifications_title')}
                            description={t('profile.notifications_description')}
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
                                            t('profile.notifications_denied'),
                                        );
                                    }
                                } catch {
                                    toast.error(
                                        t('profile.notifications_failed'),
                                    );
                                } finally {
                                    setPushBusy(false);
                                }
                            }}
                        >
                            {pushBusy
                                ? t('profile.notifications_enabling')
                                : t('profile.notifications_enable')}
                        </Button>
                    </div>
                )}
            </SettingsLayout>
        </>
    );
}
