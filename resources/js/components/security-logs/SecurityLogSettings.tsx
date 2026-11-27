import {
    SettingsRow,
    SettingsSaveButton,
    SettingsSection,
} from '@/components/common/settings';
import { Input } from '@/components/ui/input';
import { usePermissions } from '@/hooks/usePermissions';
import axiosInstance from '@/lib/axios';
import { mapFirstValidationErrors } from '@/utils/errorHandling';
import { useState } from 'react';

export type SecurityLogSettingsData = {
    security_log_retention_days?: number | string | null;
    security_log_failed_login_threshold?: number | string | null;
    security_log_failed_login_window_minutes?: number | string | null;
};

export function SecurityLogSettings({
    settings,
    onSaved,
}: Readonly<{
    settings: SecurityLogSettingsData | undefined;
    onSaved?: () => Promise<void> | void;
}>) {
    const { can } = usePermissions();
    const canEdit = can('admin_setting.edit');
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const handleSubmit = async (e: React.SubmitEvent<HTMLFormElement>) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});
        setRecentlySuccessful(false);

        try {
            const formData = new FormData(e.currentTarget);
            await axiosInstance.put(
                '/api/admin-settings',
                Object.fromEntries(formData.entries()),
            );
            await onSaved?.();
            setRecentlySuccessful(true);
            setTimeout(() => setRecentlySuccessful(false), 3000);
        } catch (error: unknown) {
            const newErrors = mapFirstValidationErrors(error);
            if (Object.keys(newErrors).length > 0) {
                setErrors(newErrors);
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <SettingsSection
            title="Security Logs"
            description="Retention and alert threshold for the security log. Records older than the retention period are deleted daily."
        >
            <form
                id="security-log-settings-form"
                onSubmit={handleSubmit}
                className="space-y-6"
            >
                <SettingsRow
                    label="Retention (days)"
                    htmlFor="security_log_retention_days"
                    description="Between 30 and 3650 days. Default 365."
                    error={errors.security_log_retention_days}
                >
                    <Input
                        id="security_log_retention_days"
                        name="security_log_retention_days"
                        type="number"
                        min={30}
                        max={3650}
                        defaultValue={
                            settings?.security_log_retention_days ?? 365
                        }
                        disabled={!canEdit}
                    />
                </SettingsRow>

                <SettingsRow
                    label="Failed login alert threshold"
                    htmlFor="security_log_failed_login_threshold"
                    description="Warn admins when one account or IP has at least this many failed logins within the window."
                    error={errors.security_log_failed_login_threshold}
                >
                    <Input
                        id="security_log_failed_login_threshold"
                        name="security_log_failed_login_threshold"
                        type="number"
                        min={1}
                        max={100}
                        defaultValue={
                            settings?.security_log_failed_login_threshold ?? 5
                        }
                        disabled={!canEdit}
                    />
                </SettingsRow>

                <SettingsRow
                    label="Alert window (minutes)"
                    htmlFor="security_log_failed_login_window_minutes"
                    description="Time window used to count failed logins. Between 1 and 1440."
                    error={errors.security_log_failed_login_window_minutes}
                >
                    <Input
                        id="security_log_failed_login_window_minutes"
                        name="security_log_failed_login_window_minutes"
                        type="number"
                        min={1}
                        max={1440}
                        defaultValue={
                            settings?.security_log_failed_login_window_minutes ??
                            15
                        }
                        disabled={!canEdit}
                    />
                </SettingsRow>

                <SettingsSaveButton
                    formId="security-log-settings-form"
                    processing={processing}
                    recentlySuccessful={recentlySuccessful}
                    testId="save-security-log-settings"
                    hidden={!canEdit}
                />
            </form>
        </SettingsSection>
    );
}
