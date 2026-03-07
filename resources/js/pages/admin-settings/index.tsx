import { Helmet } from 'react-helmet-async';
import { useState } from 'react';
import axios from '@/lib/axios';
import { useQuery } from '@tanstack/react-query';
import { Loader2 } from 'lucide-react';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import AdminSettingsLayout from '@/layouts/admin-settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
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

function GeneralSettings({ settings }: { settings: SettingsData['general'] }) {
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});
        setRecentlySuccessful(false);

        try {
            const formData = new FormData(e.currentTarget);
            await axios.post('/api/admin-settings', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                params: {
                    _method: 'PUT', // Laravel needs _method=PUT for file uploads when using PUT
                }
            });
            setRecentlySuccessful(true);
            setTimeout(() => setRecentlySuccessful(false), 3000);
        } catch (error: any) {
            if (error.response?.status === 422) {
                const newErrors: Record<string, string> = {};
                Object.keys(error.response.data.errors).forEach((key) => {
                    newErrors[key] = error.response.data.errors[key][0];
                });
                setErrors(newErrors);
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="space-y-6">
            <HeadingSmall
                title="General Settings"
                description="Configure your company information"
            />

            <form
                onSubmit={handleSubmit}
                data-testid="general-settings-form"
                className="space-y-6"
                encType="multipart/form-data"
            >
                <div className="grid gap-2">
                    <Label htmlFor="company_name">Company Name</Label>
                    <Input
                        id="company_name"
                        name="company_name"
                        defaultValue={settings?.company_name ?? ''}
                        placeholder="Enter company name"
                        className="mt-1 block w-full"
                    />
                    {errors.company_name && (
                        <p className="text-sm text-destructive">
                            {errors.company_name}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="company_address">
                        Company Address
                    </Label>
                    <Input
                        id="company_address"
                        name="company_address"
                        defaultValue={settings?.company_address ?? ''}
                        placeholder="Enter company address"
                        className="mt-1 block w-full"
                    />
                    {errors.company_address && (
                        <p className="text-sm text-destructive">
                            {errors.company_address}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="company_phone">Company Phone</Label>
                    <Input
                        id="company_phone"
                        name="company_phone"
                        defaultValue={settings?.company_phone ?? ''}
                        placeholder="Enter company phone"
                        className="mt-1 block w-full"
                    />
                    {errors.company_phone && (
                        <p className="text-sm text-destructive">
                            {errors.company_phone}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="company_email">Company Email</Label>
                    <Input
                        id="company_email"
                        name="company_email"
                        type="email"
                        defaultValue={settings?.company_email ?? ''}
                        placeholder="Enter company email"
                        className="mt-1 block w-full"
                    />
                    {errors.company_email && (
                        <p className="text-sm text-destructive">
                            {errors.company_email}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="company_logo">Company Logo (SVG)</Label>
                    {settings?.company_logo_url && (
                        <img
                            src={settings.company_logo_url}
                            alt="Current company logo"
                            className="h-14 w-auto rounded border object-contain p-2"
                        />
                    )}
                    <Input
                        id="company_logo"
                        name="company_logo"
                        type="file"
                        accept=".svg,image/svg+xml"
                        className="mt-1 block w-full"
                    />
                    <p className="text-sm text-muted-foreground">
                        Upload file SVG. Jika kosong, aplikasi tetap menggunakan logo default.
                    </p>
                    {errors.company_logo && (
                        <p className="text-sm text-destructive">
                            {errors.company_logo}
                        </p>
                    )}
                </div>

                <div className="flex items-center gap-4">
                    <Button
                        disabled={processing}
                        className="min-w-24"
                        data-testid="save-general-settings"
                    >
                        {processing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                        Save
                    </Button>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out duration-300"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out duration-300"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-green-600">
                            Saved
                        </p>
                    </Transition>
                </div>
            </form>
        </div>
    );
}

function RegionalSettings({
    settings,
}: {
    settings: SettingsData['regional'];
}) {
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});
        setRecentlySuccessful(false);

        try {
            const formData = new FormData(e.currentTarget);
            await axios.put('/api/admin-settings', Object.fromEntries(formData));
            setRecentlySuccessful(true);
            setTimeout(() => setRecentlySuccessful(false), 3000);
        } catch (error: any) {
            if (error.response?.status === 422) {
                const newErrors: Record<string, string> = {};
                Object.keys(error.response.data.errors).forEach((key) => {
                    newErrors[key] = error.response.data.errors[key][0];
                });
                setErrors(newErrors);
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="space-y-6">
            <HeadingSmall
                title="Regional Settings"
                description="Configure timezone, currency, and format preferences"
            />

            <form
                onSubmit={handleSubmit}
                data-testid="regional-settings-form"
                className="space-y-6"
            >
                <div className="grid gap-2">
                    <Label htmlFor="timezone">Timezone</Label>
                    <Input
                        id="timezone"
                        name="timezone"
                        defaultValue={settings?.timezone ?? 'Asia/Jakarta'}
                        placeholder="e.g. Asia/Jakarta"
                        className="mt-1 block w-full"
                    />
                    {errors.timezone && (
                        <p className="text-sm text-destructive">
                            {errors.timezone}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="currency">Currency</Label>
                    <Input
                        id="currency"
                        name="currency"
                        defaultValue={settings?.currency ?? 'IDR'}
                        placeholder="e.g. IDR"
                        className="mt-1 block w-full"
                    />
                    {errors.currency && (
                        <p className="text-sm text-destructive">
                            {errors.currency}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="date_format">Date Format</Label>
                    <Input
                        id="date_format"
                        name="date_format"
                        defaultValue={settings?.date_format ?? 'd/m/Y'}
                        placeholder="e.g. d/m/Y"
                        className="mt-1 block w-full"
                    />
                    {errors.date_format && (
                        <p className="text-sm text-destructive">
                            {errors.date_format}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="number_format_decimal">
                        Decimal Separator
                    </Label>
                    <Input
                        id="number_format_decimal"
                        name="number_format_decimal"
                        defaultValue={
                            settings?.number_format_decimal ?? ','
                        }
                        placeholder="e.g. ,"
                        className="mt-1 block w-full"
                    />
                    {errors.number_format_decimal && (
                        <p className="text-sm text-destructive">
                            {errors.number_format_decimal}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="number_format_thousand">
                        Thousand Separator
                    </Label>
                    <Input
                        id="number_format_thousand"
                        name="number_format_thousand"
                        defaultValue={
                            settings?.number_format_thousand ?? '.'
                        }
                        placeholder="e.g. ."
                        className="mt-1 block w-full"
                    />
                    {errors.number_format_thousand && (
                        <p className="text-sm text-destructive">
                            {errors.number_format_thousand}
                        </p>
                    )}
                </div>

                <div className="flex items-center gap-4">
                    <Button
                        disabled={processing}
                        className="min-w-24"
                        data-testid="save-regional-settings"
                    >
                        {processing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                        Save
                    </Button>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out duration-300"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out duration-300"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-green-600">
                            Saved
                        </p>
                    </Transition>
                </div>
            </form>
        </div>
    );
}

function SmtpSettings({ settings }: { settings: SettingsData['smtp'] }) {
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});
        setRecentlySuccessful(false);

        try {
            const formData = new FormData(e.currentTarget);
            await axios.put('/api/admin-settings', Object.fromEntries(formData));
            setRecentlySuccessful(true);
            setTimeout(() => setRecentlySuccessful(false), 3000);
        } catch (error: any) {
            if (error.response?.status === 422) {
                const newErrors: Record<string, string> = {};
                Object.keys(error.response.data.errors).forEach((key) => {
                    newErrors[key] = error.response.data.errors[key][0];
                });
                setErrors(newErrors);
            }
        } finally {
            setProcessing(false);
        }
    };

    const [testProcessing, setTestProcessing] = useState(false);
    const [testRecentlySuccessful, setTestRecentlySuccessful] = useState(false);
    const [testErrors, setTestErrors] = useState<Record<string, string>>({});

    const handleTestSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setTestProcessing(true);
        setTestErrors({});
        setTestRecentlySuccessful(false);

        try {
            const formData = new FormData(e.currentTarget);
            await axios.post('/api/admin-settings/test-smtp', Object.fromEntries(formData));
            setTestRecentlySuccessful(true);
            setTimeout(() => setTestRecentlySuccessful(false), 3000);
        } catch (error: any) {
            if (error.response?.status === 422) {
                const newErrors: Record<string, string> = {};
                Object.keys(error.response.data.errors).forEach((key) => {
                    newErrors[key] = error.response.data.errors[key][0];
                });
                setTestErrors(newErrors);
            } else if (error.response?.data?.message) {
                setTestErrors({ test_email: error.response.data.message });
            }
        } finally {
            setTestProcessing(false);
        }
    };

    return (
        <div className="space-y-6">
            <HeadingSmall
                title="SMTP Settings"
                description="Configure outgoing email server (SMTP) details"
            />

            <form
                onSubmit={handleSubmit}
                data-testid="smtp-settings-form"
                className="space-y-6"
            >
                <div className="grid gap-2">
                    <Label htmlFor="mail_host">Mail Host</Label>
                    <Input
                        id="mail_host"
                        name="mail_host"
                        defaultValue={settings?.mail_host ?? ''}
                        placeholder="e.g. smtp.mailgun.org"
                        className="mt-1 block w-full"
                    />
                    {errors.mail_host && (
                        <p className="text-sm text-destructive">
                            {errors.mail_host}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="mail_port">Mail Port</Label>
                    <Input
                        id="mail_port"
                        name="mail_port"
                        defaultValue={settings?.mail_port ?? ''}
                        placeholder="e.g. 587"
                        className="mt-1 block w-full"
                    />
                    {errors.mail_port && (
                        <p className="text-sm text-destructive">
                            {errors.mail_port}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="mail_username">Mail Username</Label>
                    <Input
                        id="mail_username"
                        name="mail_username"
                        defaultValue={settings?.mail_username ?? ''}
                        placeholder="e.g. postmaster@yourdomain.com"
                        className="mt-1 block w-full"
                    />
                    {errors.mail_username && (
                        <p className="text-sm text-destructive">
                            {errors.mail_username}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="mail_password">Mail Password</Label>
                    <Input
                        id="mail_password"
                        name="mail_password"
                        type="password"
                        defaultValue={settings?.mail_password ?? ''}
                        placeholder="e.g. secret123"
                        className="mt-1 block w-full"
                    />
                    {errors.mail_password && (
                        <p className="text-sm text-destructive">
                            {errors.mail_password}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="mail_encryption">Mail Encryption</Label>
                    <Input
                        id="mail_encryption"
                        name="mail_encryption"
                        defaultValue={settings?.mail_encryption ?? ''}
                        placeholder="e.g. tls, ssl, or leave empty"
                        className="mt-1 block w-full"
                    />
                    {errors.mail_encryption && (
                        <p className="text-sm text-destructive">
                            {errors.mail_encryption}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="mail_from_address">From Address</Label>
                    <Input
                        id="mail_from_address"
                        name="mail_from_address"
                        type="email"
                        defaultValue={settings?.mail_from_address ?? ''}
                        placeholder="e.g. noreply@yourdomain.com"
                        className="mt-1 block w-full"
                    />
                    {errors.mail_from_address && (
                        <p className="text-sm text-destructive">
                            {errors.mail_from_address}
                        </p>
                    )}
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="mail_from_name">From Name</Label>
                    <Input
                        id="mail_from_name"
                        name="mail_from_name"
                        defaultValue={settings?.mail_from_name ?? ''}
                        placeholder="e.g. My Company"
                        className="mt-1 block w-full"
                    />
                    {errors.mail_from_name && (
                        <p className="text-sm text-destructive">
                            {errors.mail_from_name}
                        </p>
                    )}
                </div>

                <div className="flex items-center gap-4">
                    <Button
                        disabled={processing}
                        className="min-w-24"
                        data-testid="save-smtp-settings"
                    >
                        {processing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                        Save
                    </Button>

                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out duration-300"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out duration-300"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-green-600">
                            Saved
                        </p>
                    </Transition>
                </div>
            </form>

            <Separator className="my-8" />

            <div className="space-y-4">
                <HeadingSmall
                    title="Test SMTP Configuration"
                    description="Send a test email to verify your settings are correct"
                />

                <form
                    onSubmit={handleTestSubmit}
                    data-testid="test-smtp-form"
                    className="flex w-full items-start gap-4"
                >
                    <div className="flex-1 space-y-2">
                        <div className="flex w-full items-center gap-4">
                            <div className="flex-1">
                                <Label htmlFor="test_email" className="sr-only">Test Email</Label>
                                <Input
                                    id="test_email"
                                    name="test_email"
                                    type="email"
                                    placeholder="Enter email to test..."
                                    className="w-full"
                                />
                            </div>
                            <Button
                                disabled={testProcessing}
                                className="min-w-32"
                                data-testid="send-test-email"
                            >
                                {testProcessing ? <Loader2 className="mr-2 h-4 w-4 animate-spin" /> : null}
                                {testProcessing ? 'Sending...' : 'Send Test Email'}
                            </Button>
                        </div>

                        {testErrors.test_email && (
                            <p className="text-sm text-destructive">
                                {testErrors.test_email}
                            </p>
                        )}

                        <Transition
                            show={testRecentlySuccessful}
                            enter="transition ease-in-out duration-300"
                            enterFrom="opacity-0"
                            leave="transition ease-in-out duration-300"
                            leaveTo="opacity-0"
                        >
                            <p className="text-sm text-green-600">
                                Test email sent successfully! Check your inbox.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>
        </div>
    );
}

interface AdminSettingsResponse {
    settings: SettingsData;
}

export default function AdminSettings() {
    // Determine current group from URL query param
    const urlParams =
        typeof window !== 'undefined'
            ? new URLSearchParams(window.location.search)
            : new URLSearchParams();
    const currentGroup = urlParams.get('group') || 'general';

    const { data, isLoading, error } = useQuery<AdminSettingsResponse>({
        queryKey: ['admin-settings'],
        queryFn: async () => {
            const response = await axios.get('/api/admin-settings');
            return response.data;
        },
    });

    if (isLoading) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Helmet><title>Admin Settings</title></Helmet>
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
                <Helmet><title>Admin Settings</title></Helmet>
                <div className="flex h-full items-center justify-center p-4 text-destructive">
                    Error loading settings.
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet><title>Admin Settings</title></Helmet>

            <AdminSettingsLayout currentGroup={currentGroup}>
                {currentGroup === 'general' && (
                    <GeneralSettings settings={data.settings.general} />
                )}
                {currentGroup === 'regional' && (
                    <RegionalSettings settings={data.settings.regional} />
                )}
                {currentGroup === 'smtp' && (
                    <SmtpSettings settings={data.settings.smtp} />
                )}
            </AdminSettingsLayout>
        </AppLayout>
    );
}
