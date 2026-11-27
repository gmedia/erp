import DashboardPageShell from '@/components/common/DashboardPageShell';
import { PageBanner, type PageBannerTone } from '@/components/common/PageBanner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/contexts/i18n-context';
import {
    useRunSystemHealthCheck,
    useSystemHealth,
    type SystemHealthComponent,
    type SystemHealthData,
    type SystemHealthStatus,
} from '@/hooks/useSystemHealth';
import { BreadcrumbItem } from '@/types';
import { Loader2, Play } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'System Health',
        titleKey: 'menu.system_health',
        href: '/system-health',
    },
];

const STATUS_TEXT_CLASS: Record<SystemHealthStatus, string> = {
    ok: 'text-success',
    warning: 'text-warning',
    failed: 'text-destructive-foreground',
    crashed: 'text-destructive-foreground',
    skipped: 'text-muted-foreground',
    unknown: 'text-muted-foreground',
};

const OVERALL_TONE: Record<SystemHealthData['overall'], PageBannerTone> = {
    ok: 'success',
    warning: 'warning',
    failed: 'destructive',
    unknown: 'info',
};

function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '-';
    }
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? '-' : date.toLocaleString();
}

function StatusLabel({ status }: Readonly<{ status: SystemHealthStatus }>) {
    const { t } = useTranslation();

    return (
        <span className={`text-sm font-medium ${STATUS_TEXT_CLASS[status]}`}>
            {t(`system_health.status.${status}`)}
        </span>
    );
}

function ComponentCard({
    component,
    checkedAt,
}: Readonly<{ component: SystemHealthComponent; checkedAt: string | null }>) {
    const { t } = useTranslation();

    return (
        <Card className="gap-3 bg-card p-5" data-testid="system-health-card">
            <CardHeader className="flex flex-row items-start justify-between gap-2 p-0">
                <CardTitle className="text-base font-semibold">
                    {component.label}
                </CardTitle>
                <StatusLabel status={component.status} />
            </CardHeader>
            <CardContent className="space-y-1 p-0">
                {component.summary && (
                    <p className="text-2xl font-semibold tabular-nums">
                        {component.summary}
                    </p>
                )}
                {component.message && (
                    <p className="text-sm text-muted-foreground">
                        {component.message}
                    </p>
                )}
                <p className="text-xs text-muted-foreground">
                    {t('system_health.checked_at')}: {formatDateTime(checkedAt)}
                </p>
            </CardContent>
        </Card>
    );
}

export default function SystemHealthPage() {
    const { t } = useTranslation();
    const query = useSystemHealth();
    const runCheck = useRunSystemHealthCheck();
    const data = query.data;

    const overallTone = data ? OVERALL_TONE[data.overall] : 'info';
    const overallTitle = data
        ? t(`system_health.overall.${data.overall}`)
        : t('system_health.title');
    const isBusy = query.isFetching || runCheck.isPending;

    return (
        <DashboardPageShell
            title="System Health"
            heading="System Health"
            headingKey="menu.system_health"
            description="Status komponen sistem, riwayat masalah, dan informasi versi."
            descriptionKey="system_health.description"
            breadcrumbs={breadcrumbs}
            toolbar={
                <Button
                    size="sm"
                    className="w-full gap-2 sm:w-auto"
                    onClick={() => runCheck.mutate()}
                    disabled={isBusy}
                    data-testid="system-health-run-check"
                >
                    {runCheck.isPending ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                        <Play className="h-4 w-4" />
                    )}
                    {t('system_health.run_check')}
                </Button>
            }
            isLoading={query.isLoading}
            isError={query.isError}
            error={query.error}
            errorTitle={t('system_health.load_failed')}
            errorMessage={t('system_health.load_failed')}
            refetch={() => query.refetch()}
        >
            {data && (
                <>
                    <div data-testid="system-health-overall">
                        <PageBanner tone={overallTone} title={overallTitle}>
                            {data.checked_at
                                ? `${t('system_health.checked_at')}: ${formatDateTime(data.checked_at)}`
                                : t('system_health.never_checked')}
                        </PageBanner>
                    </div>

                    <section className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {data.components.map((component) => (
                            <ComponentCard
                                key={component.name}
                                component={component}
                                checkedAt={data.checked_at}
                            />
                        ))}
                    </section>

                    <div className="grid gap-4 lg:grid-cols-2">
                        <Card className="gap-3 bg-card p-5">
                            <CardHeader className="p-0">
                                <CardTitle className="text-base font-semibold">
                                    {t('system_health.history_title')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                {data.history.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        {t('system_health.history_empty')}
                                    </p>
                                ) : (
                                    <ul className="divide-y divide-border">
                                        {data.history.map((item, index) => (
                                            <li
                                                key={`${item.check}-${item.checked_at}-${index}`}
                                                className="flex flex-col gap-0.5 py-2"
                                            >
                                                <div className="flex items-center justify-between gap-2">
                                                    <span className="font-medium">
                                                        {item.check}
                                                    </span>
                                                    <StatusLabel status={item.status} />
                                                </div>
                                                {item.message && (
                                                    <span className="text-sm text-muted-foreground">
                                                        {item.message}
                                                    </span>
                                                )}
                                                <span className="text-xs text-muted-foreground tabular-nums">
                                                    {formatDateTime(item.checked_at)}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        <Card className="gap-3 bg-card p-5">
                            <CardHeader className="p-0">
                                <CardTitle className="text-base font-semibold">
                                    {t('system_health.version_title')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-0">
                                <dl className="grid grid-cols-[max-content_1fr] gap-x-6 gap-y-2 text-sm">
                                    <dt className="text-muted-foreground">
                                        {t('system_health.version.app')}
                                    </dt>
                                    <dd className="tabular-nums">{data.version.app}</dd>
                                    <dt className="text-muted-foreground">
                                        {t('system_health.version.php')}
                                    </dt>
                                    <dd className="tabular-nums">{data.version.php}</dd>
                                    <dt className="text-muted-foreground">
                                        {t('system_health.version.laravel')}
                                    </dt>
                                    <dd className="tabular-nums">{data.version.laravel}</dd>
                                </dl>
                            </CardContent>
                        </Card>
                    </div>
                </>
            )}
        </DashboardPageShell>
    );
}
