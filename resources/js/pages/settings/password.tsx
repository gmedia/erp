import axios from '@/lib/axios';
import { isAxiosError } from 'axios';
import { useRef, useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { toast } from 'sonner';

import {
    SettingsRow,
    SettingsSaveButton,
    SettingsSection,
} from '@/components/common/settings';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';

import { Input } from '@/components/ui/input';
import { useToastTranslator } from '@/utils/i18nFallback';

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
    const tToast = useToastTranslator('settings_toast');

    const handleSubmit = async (e: React.SubmitEvent<HTMLFormElement>) => {
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

            toast.success(
                tToast('password_updated', 'Password updated successfully'),
            );

            setTimeout(() => setRecentlySuccessful(false), 2000);
        } catch (error: unknown) {
            if (isAxiosError(error) && error.response?.status === 422) {
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
                toast.error(
                    tToast(
                        'password_update_failed',
                        'An error occurred. Please try again later.',
                    ),
                );
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
                <SettingsSection
                    title="Update password"
                    description="Ensure your account is using a long, random password to stay secure"
                    actions={
                        <SettingsSaveButton
                            formId="password-form"
                            processing={processing}
                            recentlySuccessful={recentlySuccessful}
                            dataTest="update-password-button"
                            label="Save password"
                        />
                    }
                >
                    <form id="password-form" onSubmit={handleSubmit}>
                        <SettingsRow
                            label="Current password"
                            htmlFor="current_password"
                            description="Confirm it is you before changing the password."
                            error={errors.current_password}
                        >
                            <Input
                                id="current_password"
                                ref={currentPasswordInput}
                                name="current_password"
                                type="password"
                                autoComplete="current-password"
                                placeholder="Current password"
                                required
                            />
                        </SettingsRow>

                        <SettingsRow
                            label="New password"
                            htmlFor="password"
                            description="Use a long, unique password."
                            error={errors.password}
                        >
                            <Input
                                id="password"
                                ref={passwordInput}
                                name="password"
                                type="password"
                                autoComplete="new-password"
                                placeholder="New password"
                                required
                            />
                        </SettingsRow>

                        <SettingsRow
                            label="Confirm password"
                            htmlFor="password_confirmation"
                            description="Type the new password again."
                            error={errors.password_confirmation}
                        >
                            <Input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                autoComplete="new-password"
                                placeholder="Confirm password"
                                required
                            />
                        </SettingsRow>
                    </form>
                </SettingsSection>
            </SettingsLayout>
        </AppLayout>
    );
}
