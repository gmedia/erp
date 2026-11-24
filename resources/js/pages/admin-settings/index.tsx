import {
    SettingsRow,
    SettingsSaveButton,
    SettingsSection,
} from '@/components/common/settings';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useAuth } from '@/contexts/auth-context';
import { useTranslation } from '@/contexts/i18n-context';
import { usePermissions } from '@/hooks/usePermissions';
import AdminSettingsLayout from '@/layouts/admin-settings/layout';
import AppLayout from '@/layouts/app-layout';
import axiosInstance from '@/lib/axios';
import { type BreadcrumbItem } from '@/types';
import { setRegionalDateFormatSettings } from '@/utils/date-format';
import { mapFirstValidationErrors } from '@/utils/errorHandling';
import { setRegionalNumberFormatSettings } from '@/utils/number-format';
import { Transition } from '@headlessui/react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { Loader2 } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { useSearchParams } from 'react-router-dom';
interface SettingsData {
    general?: {
        company_name?: string;
        company_address?: string;
        company_phone?: string;
        company_email?: string;
        company_logo_url?: string | null;
    };
    regional?: {
        timezone?: string;
        currency?: string;
        date_format?: string;
        number_format_decimal?: string;
        number_format_thousand?: string;
        number_format_hide_decimal?: boolean;
    };
    smtp?: {
        mail_host?: string;
        mail_port?: string;
        mail_username?: string;
        mail_password?: string;
        mail_encryption?: string;
        mail_from_address?: string;
        mail_from_name?: string;
    };
    authentication?: {
        social_login_enabled?: boolean;
        password_login_enabled?: boolean;
        social_login_allow_personal?: boolean;
        social_login_auto_link_by_email?: boolean;
        social_login_jit_provisioning?: boolean;
        social_login_allowed_domains?: string[];
        social_login_allowed_tenants?: string[];
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Admin Settings',
        href: '/admin-settings',
    },
];

const CURRENCY_OPTIONS = [
    { value: 'IDR', label: 'IDR - Indonesian Rupiah' },
] as const;

async function submitJsonAdminSettings(
    e: React.SubmitEvent<HTMLFormElement>,
    setProcessing: (value: boolean) => void,
    setErrors: (value: Record<string, string>) => void,
    setRecentlySuccessful: (value: boolean) => void,
) {
    e.preventDefault();
    setProcessing(true);
    setErrors({});
    setRecentlySuccessful(false);

    try {
        const formData = new FormData(e.currentTarget);
        await axiosInstance.put(
            '/api/admin-settings',
            Object.fromEntries(formData),
        );
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
}

function GeneralSettings({
    settings,
    onSaved,
}: Readonly<{
    settings: SettingsData['general'];
    onSaved?: () => Promise<void> | void;
}>) {
    const { t } = useTranslation();
    const { can } = usePermissions();
    const canEdit = can('admin_setting.edit');
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [logoFileName, setLogoFileName] = useState<string>('');
    const logoInputRef = useRef<HTMLInputElement>(null);

    const handleSubmit = async (e: React.SubmitEvent<HTMLFormElement>) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});
        setRecentlySuccessful(false);

        try {
            const formData = new FormData(e.currentTarget);
            const logoInput =
                e.currentTarget.elements.namedItem('company_logo');
            const payload = Object.fromEntries(formData.entries()) as Record<
                string,
                FormDataEntryValue
            >;

            delete payload.company_logo;

            if (logoInput instanceof HTMLInputElement && logoInput.files?.[0]) {
                payload.company_logo_svg = await logoInput.files[0].text();
            }

            await axiosInstance.post('/api/admin-settings', payload);

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
            title="General Settings"
            description="Configure your company information"
            actions={
                <SettingsSaveButton
                    formId="general-settings-form"
                    processing={processing}
                    recentlySuccessful={recentlySuccessful}
                    testId="save-general-settings"
                    hidden={!canEdit}
                />
            }
        >
            <form
                id="general-settings-form"
                onSubmit={handleSubmit}
                data-testid="general-settings-form"
                encType="multipart/form-data"
            >
                <SettingsRow
                    label="Company Name"
                    htmlFor="company_name"
                    description="Shown in the app header, documents, and emails."
                    error={errors.company_name}
                >
                    <Input
                        id="company_name"
                        name="company_name"
                        defaultValue={settings?.company_name ?? ''}
                        placeholder="Enter company name"
                    />
                </SettingsRow>

                <SettingsRow
                    label="Company Address"
                    htmlFor="company_address"
                    description="Printed on documents such as invoices and purchase orders."
                    error={errors.company_address}
                >
                    <Input
                        id="company_address"
                        name="company_address"
                        defaultValue={settings?.company_address ?? ''}
                        placeholder="Enter company address"
                    />
                </SettingsRow>

                <SettingsRow
                    label="Company Phone"
                    htmlFor="company_phone"
                    description="Main contact number for the company."
                    error={errors.company_phone}
                >
                    <Input
                        id="company_phone"
                        name="company_phone"
                        defaultValue={settings?.company_phone ?? ''}
                        placeholder="Enter company phone"
                    />
                </SettingsRow>

                <SettingsRow
                    label="Company Email"
                    htmlFor="company_email"
                    description="Main contact email for the company."
                    error={errors.company_email}
                >
                    <Input
                        id="company_email"
                        name="company_email"
                        type="email"
                        defaultValue={settings?.company_email ?? ''}
                        placeholder="Enter company email"
                    />
                </SettingsRow>

                <SettingsRow
                    label="Company Logo (SVG)"
                    htmlFor="company_logo"
                    description="Upload file SVG. Jika kosong, aplikasi tetap menggunakan logo default."
                    error={errors.company_logo}
                >
                    {settings?.company_logo_url && (
                        <img
                            src={settings.company_logo_url}
                            alt="Current company logo"
                            className="h-14 w-auto rounded-md bg-card object-contain p-2"
                        />
                    )}
                    <div className="flex flex-wrap items-center gap-2">
                        <input
                            ref={logoInputRef}
                            id="company_logo"
                            name="company_logo"
                            type="file"
                            accept=".svg,image/svg+xml"
                            className="hidden"
                            onChange={(event) =>
                                setLogoFileName(
                                    event.target.files?.[0]?.name ?? '',
                                )
                            }
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => logoInputRef.current?.click()}
                        >
                            {t('admin.selectLogo')}
                        </Button>
                        <span className="text-sm text-muted-foreground">
                            {logoFileName || t('admin.noFileSelected')}
                        </span>
                    </div>
                </SettingsRow>
            </form>
        </SettingsSection>
    );
}

function RegionalSettings({
    settings,
}: Readonly<{
    settings: SettingsData['regional'];
}>) {
    const { can } = usePermissions();
    const canEdit = can('admin_setting.edit');
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [currency, setCurrency] = useState<string>(
        settings?.currency ?? 'IDR',
    );
    const [hideDecimal, setHideDecimal] = useState<boolean>(
        Boolean(settings?.number_format_hide_decimal),
    );

    useEffect(() => {
        setCurrency(settings?.currency ?? 'IDR');
    }, [settings?.currency]);

    useEffect(() => {
        setHideDecimal(Boolean(settings?.number_format_hide_decimal));
    }, [settings?.number_format_hide_decimal]);

    const handleSubmit = async (e: React.SubmitEvent<HTMLFormElement>) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});
        setRecentlySuccessful(false);

        try {
            const formData = new FormData(e.currentTarget);
            formData.set('currency', currency);
            formData.set('number_format_hide_decimal', hideDecimal ? '1' : '0');

            const payload = Object.fromEntries(formData) as Record<
                string,
                string
            >;
            await axiosInstance.put('/api/admin-settings', payload);

            setRegionalNumberFormatSettings({
                currency,
                number_format_decimal: payload.number_format_decimal,
                number_format_thousand: payload.number_format_thousand,
                number_format_hide_decimal: hideDecimal,
            });
            setRegionalDateFormatSettings({
                date_format: payload.date_format,
            });

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
            title="Regional Settings"
            description="Configure currency and format preferences"
            actions={
                <SettingsSaveButton
                    formId="regional-settings-form"
                    processing={processing}
                    recentlySuccessful={recentlySuccessful}
                    testId="save-regional-settings"
                    hidden={!canEdit}
                />
            }
        >
            <form
                id="regional-settings-form"
                onSubmit={handleSubmit}
                data-testid="regional-settings-form"
            >
                <SettingsRow
                    label="Currency"
                    htmlFor="currency"
                    description="Default currency for amounts across all modules."
                    error={errors.currency}
                >
                    <Select value={currency} onValueChange={setCurrency}>
                        <SelectTrigger
                            id="currency"
                            className="w-full"
                            data-testid="currency-select-trigger"
                        >
                            <SelectValue placeholder="Select currency" />
                        </SelectTrigger>
                        <SelectContent>
                            {CURRENCY_OPTIONS.map((option) => (
                                <SelectItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <input type="hidden" name="currency" value={currency} />
                </SettingsRow>

                <SettingsRow
                    label="Date Format"
                    htmlFor="date_format"
                    description="PHP-style pattern, e.g. d/m/Y."
                    error={errors.date_format}
                >
                    <Input
                        id="date_format"
                        name="date_format"
                        defaultValue={settings?.date_format ?? 'd/m/Y'}
                        placeholder="e.g. d/m/Y"
                    />
                </SettingsRow>

                <SettingsRow
                    label="Decimal Separator"
                    htmlFor="number_format_decimal"
                    description="Character between whole and fractional digits."
                    error={errors.number_format_decimal}
                >
                    <Input
                        id="number_format_decimal"
                        name="number_format_decimal"
                        defaultValue={settings?.number_format_decimal ?? ','}
                        placeholder="e.g. ,"
                    />
                </SettingsRow>

                <SettingsRow
                    label="Thousand Separator"
                    htmlFor="number_format_thousand"
                    description="Character between groups of thousands."
                    error={errors.number_format_thousand}
                >
                    <Input
                        id="number_format_thousand"
                        name="number_format_thousand"
                        defaultValue={settings?.number_format_thousand ?? '.'}
                        placeholder="e.g. ."
                    />
                </SettingsRow>

                <SettingsRow
                    label="Hide Decimal Separator"
                    htmlFor="number_format_hide_decimal"
                    description="Jika aktif, semua modul menampilkan angka tanpa desimal."
                    error={errors.number_format_hide_decimal}
                >
                    <Checkbox
                        id="number_format_hide_decimal"
                        checked={hideDecimal}
                        onCheckedChange={(checked) =>
                            setHideDecimal(checked === true)
                        }
                        data-testid="hide-decimal-checkbox"
                    />
                    <input
                        type="hidden"
                        name="number_format_hide_decimal"
                        value={hideDecimal ? '1' : '0'}
                    />
                </SettingsRow>
            </form>
        </SettingsSection>
    );
}

function SmtpSettings({
    settings,
}: Readonly<{ settings: SettingsData['smtp'] }>) {
    const { can } = usePermissions();
    const canEdit = can('admin_setting.edit');
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const [testProcessing, setTestProcessing] = useState(false);
    const [testRecentlySuccessful, setTestRecentlySuccessful] = useState(false);
    const [testErrors, setTestErrors] = useState<Record<string, string>>({});

    const handleTestSubmit = async (e: React.SubmitEvent<HTMLFormElement>) => {
        e.preventDefault();
        setTestProcessing(true);
        setTestErrors({});
        setTestRecentlySuccessful(false);

        try {
            const formData = new FormData(e.currentTarget);
            await axiosInstance.post(
                '/api/admin-settings/test-smtp',
                Object.fromEntries(formData),
            );
            setTestRecentlySuccessful(true);
            setTimeout(() => setTestRecentlySuccessful(false), 3000);
        } catch (error: unknown) {
            if (axios.isAxiosError(error)) {
                if (error.response?.status === 422) {
                    const newErrors = mapFirstValidationErrors(error);
                    setTestErrors(newErrors);
                } else if (error.response?.data?.message) {
                    setTestErrors({ test_email: error.response.data.message });
                }
            }
        } finally {
            setTestProcessing(false);
        }
    };

    return (
        <div className="space-y-12">
            <SettingsSection
                title="SMTP Settings"
                description="Configure outgoing email server (SMTP) details"
                actions={
                    <SettingsSaveButton
                        formId="smtp-settings-form"
                        processing={processing}
                        recentlySuccessful={recentlySuccessful}
                        testId="save-smtp-settings"
                        hidden={!canEdit}
                    />
                }
            >
                <form
                    id="smtp-settings-form"
                    onSubmit={(e) =>
                        submitJsonAdminSettings(
                            e,
                            setProcessing,
                            setErrors,
                            setRecentlySuccessful,
                        )
                    }
                    data-testid="smtp-settings-form"
                >
                    <SettingsRow
                        label="Mail Host"
                        htmlFor="mail_host"
                        description="Hostname of the outgoing mail server."
                        error={errors.mail_host}
                    >
                        <Input
                            id="mail_host"
                            name="mail_host"
                            defaultValue={settings?.mail_host ?? ''}
                            placeholder="e.g. smtp.mailgun.org"
                        />
                    </SettingsRow>
                    <SettingsRow
                        label="Mail Port"
                        htmlFor="mail_port"
                        description="Usually 587 (TLS) or 465 (SSL)."
                        error={errors.mail_port}
                    >
                        <Input
                            id="mail_port"
                            name="mail_port"
                            defaultValue={settings?.mail_port ?? ''}
                            placeholder="e.g. 587"
                        />
                    </SettingsRow>
                    <SettingsRow
                        label="Mail Username"
                        htmlFor="mail_username"
                        description="Account used to authenticate with the server."
                        error={errors.mail_username}
                    >
                        <Input
                            id="mail_username"
                            name="mail_username"
                            defaultValue={settings?.mail_username ?? ''}
                            placeholder="e.g. postmaster@yourdomain.com"
                        />
                    </SettingsRow>
                    <SettingsRow
                        label="Mail Password"
                        htmlFor="mail_password"
                        description="Password or app token for the account above."
                        error={errors.mail_password}
                    >
                        <Input
                            id="mail_password"
                            name="mail_password"
                            type="password"
                            defaultValue={settings?.mail_password ?? ''}
                            placeholder="e.g. secret123"
                        />
                    </SettingsRow>
                    <SettingsRow
                        label="Mail Encryption"
                        htmlFor="mail_encryption"
                        description="tls, ssl, or leave empty for none."
                        error={errors.mail_encryption}
                    >
                        <Input
                            id="mail_encryption"
                            name="mail_encryption"
                            defaultValue={settings?.mail_encryption ?? ''}
                            placeholder="e.g. tls, ssl, or leave empty"
                        />
                    </SettingsRow>
                    <SettingsRow
                        label="From Address"
                        htmlFor="mail_from_address"
                        description="Sender address shown to recipients."
                        error={errors.mail_from_address}
                    >
                        <Input
                            id="mail_from_address"
                            name="mail_from_address"
                            type="email"
                            defaultValue={settings?.mail_from_address ?? ''}
                            placeholder="e.g. noreply@yourdomain.com"
                        />
                    </SettingsRow>
                    <SettingsRow
                        label="From Name"
                        htmlFor="mail_from_name"
                        description="Sender name shown to recipients."
                        error={errors.mail_from_name}
                    >
                        <Input
                            id="mail_from_name"
                            name="mail_from_name"
                            defaultValue={settings?.mail_from_name ?? ''}
                            placeholder="e.g. My Company"
                        />
                    </SettingsRow>
                </form>
            </SettingsSection>

            <SettingsSection
                title="Test SMTP Configuration"
                description="Send a test email to verify your settings are correct"
            >
                <form onSubmit={handleTestSubmit} data-testid="test-smtp-form">
                    <SettingsRow
                        label="Test Email"
                        htmlFor="test_email"
                        description="Recipient for the test message. Save your settings first."
                        error={testErrors.test_email}
                    >
                        <div className="flex gap-2">
                            <Input
                                id="test_email"
                                name="test_email"
                                type="email"
                                placeholder="Enter email to test..."
                                className="flex-1"
                            />
                            {canEdit && (
                                <Button
                                    variant="secondary"
                                    disabled={testProcessing}
                                    data-testid="send-test-email"
                                >
                                    {testProcessing ? (
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    ) : null}
                                    {testProcessing
                                        ? 'Sending...'
                                        : 'Send Test Email'}
                                </Button>
                            )}
                        </div>
                        <Transition
                            show={testRecentlySuccessful}
                            enter="transition ease-in-out duration-300"
                            enterFrom="opacity-0"
                            leave="transition ease-in-out duration-300"
                            leaveTo="opacity-0"
                        >
                            <p className="text-sm text-success">
                                Test email sent successfully! Check your inbox.
                            </p>
                        </Transition>
                    </SettingsRow>
                </form>
            </SettingsSection>
        </div>
    );
}

interface AdminSettingsResponse {
    settings: SettingsData;
}

function AuthenticationSettings({
    settings,
    onSaved,
}: Readonly<{
    settings: SettingsData['authentication'];
    onSaved?: () => Promise<void> | void;
}>) {
    const { can } = usePermissions();
    const canEdit = can('admin_setting.edit');
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [socialEnabled, setSocialEnabled] = useState(
        settings?.social_login_enabled ?? true,
    );
    const [passwordEnabled, setPasswordEnabled] = useState(
        settings?.password_login_enabled ?? true,
    );
    const [personalEnabled, setPersonalEnabled] = useState(
        settings?.social_login_allow_personal ?? false,
    );
    const [autoLink, setAutoLink] = useState(
        settings?.social_login_auto_link_by_email ?? false,
    );
    const [jit, setJit] = useState(
        settings?.social_login_jit_provisioning ?? false,
    );

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

    const toggles = [
        {
            key: 'social_login_enabled',
            label: 'Enable social login',
            description:
                'Allow sign in with Google Workspace or Microsoft 365.',
            checked: socialEnabled,
            set: setSocialEnabled,
            testId: 'social-login-enabled',
        },
        {
            key: 'password_login_enabled',
            label: 'Enable password login',
            description: 'Allow the email and password form on the login page.',
            checked: passwordEnabled,
            set: setPasswordEnabled,
            testId: 'password-login-enabled',
        },
        {
            key: 'social_login_allow_personal',
            label: 'Allow personal accounts',
            description:
                'Allow personal Google or Microsoft accounts (off by default).',
            checked: personalEnabled,
            set: setPersonalEnabled,
            testId: 'social-login-personal',
        },
        {
            key: 'social_login_auto_link_by_email',
            label: 'Auto-link by email',
            description:
                'Link a social profile automatically when the email matches.',
            checked: autoLink,
            set: setAutoLink,
            testId: 'social-login-auto-link',
        },
        {
            key: 'social_login_jit_provisioning',
            label: 'Auto-create users',
            description:
                'Create a user on first social sign in. HR provisioning is still required.',
            checked: jit,
            set: setJit,
            testId: 'social-login-jit',
        },
    ];

    return (
        <SettingsSection
            title="Authentication"
            description="Manage sign-in methods and allowed organizations"
            actions={
                <SettingsSaveButton
                    formId="authentication-settings-form"
                    processing={processing}
                    recentlySuccessful={recentlySuccessful}
                    testId="save-authentication-settings"
                    hidden={!canEdit}
                />
            }
        >
            <form
                id="authentication-settings-form"
                onSubmit={handleSubmit}
                data-testid="authentication-settings-form"
            >
                {toggles.map((toggle) => (
                    <SettingsRow
                        key={toggle.key}
                        label={toggle.label}
                        htmlFor={toggle.key}
                        description={toggle.description}
                        error={errors[toggle.key]}
                    >
                        <Checkbox
                            id={toggle.key}
                            checked={toggle.checked}
                            onCheckedChange={(checked) =>
                                toggle.set(checked === true)
                            }
                            data-testid={toggle.testId}
                        />
                        <input
                            type="hidden"
                            name={toggle.key}
                            value={toggle.checked ? '1' : '0'}
                        />
                    </SettingsRow>
                ))}

                <SettingsRow
                    label="Allowed email domains"
                    htmlFor="social_login_allowed_domains"
                    description="Comma-separated. Leave empty to deny all Workspace sign-ins."
                    error={errors.social_login_allowed_domains}
                >
                    <Textarea
                        id="social_login_allowed_domains"
                        name="social_login_allowed_domains"
                        defaultValue={(
                            settings?.social_login_allowed_domains ?? []
                        ).join(', ')}
                        placeholder="example.com, subsidiary.example"
                        rows={3}
                        data-testid="social-allowed-domains"
                    />
                </SettingsRow>

                <SettingsRow
                    label="Allowed Microsoft tenants"
                    htmlFor="social_login_allowed_tenants"
                    description="Comma-separated. Leave empty to deny all Microsoft 365 sign-ins."
                    error={errors.social_login_allowed_tenants}
                >
                    <Textarea
                        id="social_login_allowed_tenants"
                        name="social_login_allowed_tenants"
                        defaultValue={(
                            settings?.social_login_allowed_tenants ?? []
                        ).join(', ')}
                        placeholder="tenant-id"
                        rows={3}
                        data-testid="social-allowed-tenants"
                    />
                </SettingsRow>
            </form>
        </SettingsSection>
    );
}

export default function AdminSettings() {
    const [searchParams] = useSearchParams();
    const currentGroup = searchParams.get('group') || 'general';
    const queryClient = useQueryClient();
    const { refreshAuth } = useAuth();

    const handleGeneralSettingsSaved = useCallback(async () => {
        await queryClient.invalidateQueries({ queryKey: ['admin-settings'] });
        await queryClient.refetchQueries({
            queryKey: ['admin-settings'],
            type: 'active',
        });
        await refreshAuth();
    }, [queryClient, refreshAuth]);

    const { data, isLoading, error } = useQuery<AdminSettingsResponse>({
        queryKey: ['admin-settings'],
        queryFn: async () => {
            const response = await axiosInstance.get('/api/admin-settings');
            return response.data;
        },
    });

    if (isLoading) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Helmet>
                    <title>Admin Settings</title>
                </Helmet>
                <div className="flex h-full items-center justify-center p-4">
                    <Loader2 className="mr-2 h-6 w-6 animate-spin text-muted-foreground" />
                    <span>Loading settings...</span>
                </div>
            </AppLayout>
        );
    }

    if (error || !data) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Helmet>
                    <title>Admin Settings</title>
                </Helmet>
                <div className="flex h-full items-center justify-center p-4 text-destructive">
                    Error loading settings.
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>Admin Settings</title>
            </Helmet>

            <AdminSettingsLayout currentGroup={currentGroup}>
                {currentGroup === 'general' && (
                    <GeneralSettings
                        settings={data?.settings?.general}
                        onSaved={handleGeneralSettingsSaved}
                    />
                )}
                {currentGroup === 'regional' && (
                    <RegionalSettings settings={data?.settings?.regional} />
                )}
                {currentGroup === 'smtp' && (
                    <SmtpSettings settings={data?.settings?.smtp} />
                )}
                {currentGroup === 'authentication' && (
                    <AuthenticationSettings
                        settings={data?.settings?.authentication}
                        onSaved={handleGeneralSettingsSaved}
                    />
                )}
            </AdminSettingsLayout>
        </AppLayout>
    );
}
