import { PageHeader } from '@/components/common/PageHeader';
import { Sha256Field } from '@/components/mobile-app/Sha256Field';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/contexts/i18n-context';
import {
    downloadActiveMobileAppRelease,
    useActiveMobileAppRelease,
} from '@/hooks/useMobileAppReleases';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import { formatFileSize } from '@/utils/format-file-size';
import { Download, Loader2, Package, Smartphone } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import { useMemo, useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { toast } from 'sonner';

function DetailRow({
    label,
    children,
}: Readonly<{ label: string; children: React.ReactNode }>) {
    return (
        <div
            className={cn(
                'flex flex-col gap-1 border-b border-border py-3 last:border-b-0',
                'sm:flex-row sm:items-center sm:justify-between',
            )}
        >
            <span className="text-sm text-muted-foreground">{label}</span>
            <span className="text-sm font-medium">{children}</span>
        </div>
    );
}

export default function MobileAppDownloadPage() {
    const { t } = useTranslation();
    const releaseQuery = useActiveMobileAppRelease();
    const [downloading, setDownloading] = useState(false);

    const pageUrl = useMemo(() => {
        if (typeof window === 'undefined') return '/mobile-app';

        return `${window.location.origin}/mobile-app`;
    }, []);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', titleKey: 'menu.dashboard', href: '/dashboard' },
        {
            title: 'Download App',
            titleKey: 'mobileApp.download_title',
            href: '/mobile-app',
        },
    ];

    const release = releaseQuery.data ?? null;

    const handleDownload = async () => {
        if (!release) return;

        setDownloading(true);
        try {
            await downloadActiveMobileAppRelease(`${release.version_name}.apk`);
        } catch {
            toast.error(t('mobileApp.download_failed'));
        } finally {
            setDownloading(false);
        }
    };

    let content: React.ReactNode;
    if (releaseQuery.isLoading) {
        content = (
            <div className="flex items-center justify-center py-16">
                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
            </div>
        );
    } else if (!release) {
        content = (
            <Card className="bg-card p-10">
                <div className="flex flex-col items-center justify-center gap-3 text-center">
                    <Package className="h-12 w-12 text-muted-foreground" />
                    <h2 className="text-lg font-semibold">
                        {t('mobileApp.empty_download_title')}
                    </h2>
                    <p className="max-w-md text-sm text-muted-foreground">
                        {t('mobileApp.empty_download_description')}
                    </p>
                </div>
            </Card>
        );
    } else {
        content = (
            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <Card className="bg-card p-6">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div className="flex items-center gap-3">
                            <div className="flex size-10 items-center justify-center rounded-full bg-secondary">
                                <Smartphone className="size-5 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    {t('mobileApp.version_label')}
                                </p>
                                <p className="text-xl font-semibold">
                                    {release.version_name}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="mt-2">
                        <DetailRow label={t('mobileApp.size_label')}>
                            <span className="tabular-nums">
                                {formatFileSize(release.file_size)}
                            </span>
                        </DetailRow>
                        <DetailRow label={t('mobileApp.version_code_label')}>
                            <span className="tabular-nums">
                                {release.version_code}
                            </span>
                        </DetailRow>
                        <DetailRow label={t('mobileApp.released_label')}>
                            {formatDateTimeByRegionalSettings(
                                release.created_at,
                            )}
                        </DetailRow>
                        <DetailRow label={t('mobileApp.min_version_label')}>
                            <span className="tabular-nums">
                                {release.min_supported_version_code}
                            </span>
                        </DetailRow>
                        <DetailRow label={t('mobileApp.sha256_label')}>
                            <Sha256Field
                                value={release.sha256}
                                testIdPrefix="download-sha256"
                                className="sm:justify-end"
                            />
                        </DetailRow>
                    </div>

                    {release.notes ? (
                        <div className="mt-2 space-y-1">
                            <p className="text-sm text-muted-foreground">
                                {t('mobileApp.notes_label')}
                            </p>
                            <p className="text-sm whitespace-pre-line">
                                {release.notes}
                            </p>
                        </div>
                    ) : null}

                    <Button
                        type="button"
                        className="mt-4 w-full sm:w-auto"
                        onClick={() => void handleDownload()}
                        disabled={downloading}
                        data-testid="mobile-app-download-button"
                    >
                        {downloading ? (
                            <Loader2
                                className="animate-spin"
                                aria-hidden="true"
                            />
                        ) : (
                            <Download aria-hidden="true" />
                        )}
                        {downloading
                            ? t('mobileApp.downloading')
                            : t('mobileApp.download_button')}
                    </Button>
                </Card>

                <Card
                    className="hidden bg-card p-6 md:block"
                    data-testid="mobile-app-qr-card"
                >
                    <CardHeader className="p-0">
                        <CardTitle className="text-base font-semibold">
                            {t('mobileApp.qr_title')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col items-center gap-3 p-0 pt-4">
                        <div
                            className="rounded-xl bg-white p-3"
                            data-testid="mobile-app-qr"
                        >
                            <QRCodeSVG
                                value={pageUrl}
                                size={192}
                                level="M"
                                marginSize={2}
                                title={t('mobileApp.qr_title')}
                            />
                        </div>
                        <p className="text-center text-xs text-muted-foreground">
                            {t('mobileApp.qr_description')}
                        </p>
                    </CardContent>
                </Card>

                <Card className="bg-card p-6 lg:col-span-2">
                    <CardHeader className="p-0">
                        <CardTitle className="text-base font-semibold">
                            {t('mobileApp.install_title')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0 pt-3">
                        <ol className="list-decimal space-y-2 pl-5 text-sm text-muted-foreground">
                            <li>{t('mobileApp.install_step_1')}</li>
                            <li>
                                {t('mobileApp.install_step_2')}{' '}
                                {t('mobileApp.install_step_2_settings')}
                            </li>
                            <li>{t('mobileApp.install_step_3')}</li>
                        </ol>
                    </CardContent>
                </Card>
            </div>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>{t('mobileApp.download_title')}</title>
            </Helmet>

            <div className="flex flex-col gap-6 p-6">
                <PageHeader
                    title={t('mobileApp.download_title')}
                    description={t('mobileApp.download_description')}
                />
                {content}
            </div>
        </AppLayout>
    );
}
