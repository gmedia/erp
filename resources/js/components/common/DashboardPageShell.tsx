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
                <PageHeader
                    title={heading}
                    description={description}
                    actions={
                        <>
                            {toolbar}
                            <Button
                                variant="secondary"
                                size="sm"
                                onClick={() => refetch()}
                                disabled={isLoading}
                                className="gap-2"
                            >
                                <RefreshCw
                                    className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`}
                                />
                                Refresh Data
                            </Button>
                        </>
                    }
                />

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
