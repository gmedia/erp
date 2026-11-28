import { DataTablePagination } from '@/components/common/DataTablePagination';
import { PageHeader } from '@/components/common/PageHeader';
import {
    TableEmptyStateRow,
    TableLoadingRow,
    buildStandalonePaginationView,
} from '@/components/common/StandaloneTablePage';
import { MobileAppReleaseUploadDialog } from '@/components/mobile-app/MobileAppReleaseUploadDialog';
import { Sha256Field } from '@/components/mobile-app/Sha256Field';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslation } from '@/contexts/i18n-context';
import {
    useMobileAppReleases,
    useSetMobileAppReleaseActive,
} from '@/hooks/useMobileAppReleases';
import { usePermissions } from '@/hooks/usePermissions';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import { formatFileSize } from '@/utils/format-file-size';
import { Loader2, Package, Power, PowerOff, Upload } from 'lucide-react';
import { useState } from 'react';
import { Helmet } from 'react-helmet-async';
import { Navigate } from 'react-router-dom';

const DEFAULT_META = {
    current_page: 1,
    per_page: 20,
    total: 0,
    last_page: 1,
};

export default function MobileAppReleasesPage() {
    const { t } = useTranslation();
    const { can } = usePermissions();
    const canManage = can('mobile_app_release');
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(20);
    const [uploadOpen, setUploadOpen] = useState(false);

    const releasesQuery = useMobileAppReleases({ page, perPage });
    const setActive = useSetMobileAppReleaseActive();

    if (!canManage) {
        return <Navigate to="/dashboard" replace />;
    }

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', titleKey: 'menu.dashboard', href: '/dashboard' },
        {
            title: 'Mobile App Releases',
            titleKey: 'mobileApp.admin_title',
            href: '/mobile-app-releases',
        },
    ];

    const rows = releasesQuery.data?.data ?? [];
    const meta = releasesQuery.data?.meta ?? {
        ...DEFAULT_META,
        current_page: page,
        per_page: perPage,
    };
    const paginationView = buildStandalonePaginationView(meta, rows.length);

    let tableBody: React.ReactNode;
    if (releasesQuery.isLoading && rows.length === 0) {
        tableBody = (
            <TableLoadingRow
                colSpan={9}
                icon={<Loader2 className="h-8 w-8 animate-spin text-primary" />}
                message={t('mobileApp.loading')}
            />
        );
    } else if (rows.length === 0) {
        tableBody = (
            <TableEmptyStateRow
                colSpan={9}
                icon={<Package className="h-10 w-10 text-muted-foreground" />}
                title={t('mobileApp.empty_title')}
                description={t('mobileApp.empty_description')}
            />
        );
    } else {
        tableBody = rows.map((release) => (
            <TableRow
                key={release.id}
                data-testid="mobile-app-release-row"
                data-version-name={release.version_name}
            >
                <TableCell className="font-medium">
                    {release.version_name}
                </TableCell>
                <TableCell className="tabular-nums">
                    {release.version_code}
                </TableCell>
                <TableCell className="tabular-nums">
                    {release.min_supported_version_code}
                </TableCell>
                <TableCell className="whitespace-nowrap tabular-nums">
                    {formatFileSize(release.file_size)}
                </TableCell>
                <TableCell className="max-w-[220px]">
                    <Sha256Field value={release.sha256} compact />
                </TableCell>
                <TableCell>
                    <Badge variant={release.is_active ? 'success' : 'outline'}>
                        {release.is_active
                            ? t('mobileApp.status_active')
                            : t('mobileApp.status_inactive')}
                    </Badge>
                </TableCell>
                <TableCell className="text-muted-foreground">
                    {release.uploader_name ?? '—'}
                </TableCell>
                <TableCell className="whitespace-nowrap text-muted-foreground">
                    {formatDateTimeByRegionalSettings(release.created_at)}
                </TableCell>
                <TableCell className="text-right">
                    <Button
                        type="button"
                        size="sm"
                        variant={release.is_active ? 'secondary' : 'default'}
                        disabled={setActive.isPending}
                        onClick={() =>
                            setActive.mutate({
                                id: release.id,
                                active: !release.is_active,
                            })
                        }
                        data-testid="mobile-app-release-toggle"
                    >
                        {release.is_active ? (
                            <PowerOff aria-hidden="true" />
                        ) : (
                            <Power aria-hidden="true" />
                        )}
                        {release.is_active
                            ? t('mobileApp.deactivate')
                            : t('mobileApp.activate')}
                    </Button>
                </TableCell>
            </TableRow>
        ));
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>{t('mobileApp.admin_title')}</title>
            </Helmet>

            <div className="flex flex-col gap-6 p-6">
                <PageHeader
                    title={t('mobileApp.admin_title')}
                    description={t('mobileApp.admin_description')}
                    actions={
                        <Button
                            type="button"
                            onClick={() => setUploadOpen(true)}
                            data-testid="mobile-app-upload-open"
                        >
                            <Upload aria-hidden="true" />
                            {t('mobileApp.upload_button')}
                        </Button>
                    }
                />

                <div className="flex flex-col gap-4">
                    <Table>
                        <TableHeader className="[&_th]:text-muted-foreground">
                            <TableRow>
                                <TableHead>
                                    {t('mobileApp.columns.version')}
                                </TableHead>
                                <TableHead>
                                    {t('mobileApp.columns.code')}
                                </TableHead>
                                <TableHead>
                                    {t('mobileApp.columns.min_version')}
                                </TableHead>
                                <TableHead>
                                    {t('mobileApp.columns.size')}
                                </TableHead>
                                <TableHead>
                                    {t('mobileApp.columns.sha256')}
                                </TableHead>
                                <TableHead>
                                    {t('mobileApp.columns.status')}
                                </TableHead>
                                <TableHead>
                                    {t('mobileApp.columns.uploaded_by')}
                                </TableHead>
                                <TableHead>
                                    {t('mobileApp.columns.date')}
                                </TableHead>
                                <TableHead className="text-right">
                                    <span className="sr-only">
                                        {t('common.actions')}
                                    </span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>{tableBody}</TableBody>
                    </Table>

                    <DataTablePagination
                        pagination={paginationView}
                        onPageChange={setPage}
                        onPageSizeChange={(nextPerPage) => {
                            setPerPage(nextPerPage);
                            setPage(1);
                        }}
                        disabled={releasesQuery.isFetching}
                    />
                </div>
            </div>

            <MobileAppReleaseUploadDialog
                open={uploadOpen}
                onOpenChange={setUploadOpen}
            />
        </AppLayout>
    );
}
