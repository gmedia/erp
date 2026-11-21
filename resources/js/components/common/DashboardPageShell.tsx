import { PageBanner } from '@/components/common/PageBanner';
import { PageHeader } from '@/components/common/PageHeader';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { RefreshCw } from 'lucide-react';
import { Helmet } from 'react-helmet-async';

interface DashboardPageShellProps {
    title: string;
    heading: string;
    description: string;
    breadcrumbs: BreadcrumbItem[];
    toolbar?: React.ReactNode;
    isLoading: boolean;
    isError: boolean;
    error?: Error | null;
    errorTitle?: string;
    errorMessage?: string;
    refetch: () => void;
    children: React.ReactNode;
}

export default function DashboardPageShell({
    title,
    heading,
    description,
    breadcrumbs,
    toolbar,
    isLoading,
    isError,
    error,
    errorTitle = 'Error Loading Dashboard',
    errorMessage = 'Failed to fetch dashboard data from the server. Please try refreshing.',
    refetch,
    children,
}: Readonly<DashboardPageShellProps>) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>{title}</title>
            </Helmet>
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 pb-12 md:p-6">
                <div className="flex flex-col gap-3">
                    <PageHeader title={heading} description={description} />
                    {toolbar ? (
                        <div className="flex w-full flex-nowrap items-center gap-2 overflow-x-auto pb-1">
                            {toolbar}
                            <Button
                                variant="secondary"
                                size="sm"
                                onClick={() => refetch()}
                                disabled={isLoading}
                                className="ml-auto shrink-0 gap-2"
                            >
                                <RefreshCw
                                    className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`}
                                />
                                Refresh Data
                            </Button>
                        </div>
                    ) : (
                        <div className="flex w-full justify-end">
                            <Button
                                variant="secondary"
                                size="sm"
                                onClick={() => refetch()}
                                disabled={isLoading}
                                className="shrink-0 gap-2"
                            >
                                <RefreshCw
                                    className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`}
                                />
                                Refresh Data
                            </Button>
                        </div>
                    )}
                </div>

                {isError && (
                    <PageBanner tone="destructive" title={errorTitle}>
                        {error instanceof Error ? error.message : errorMessage}
                    </PageBanner>
                )}

                {children}
            </div>
        </AppLayout>
    );
}
