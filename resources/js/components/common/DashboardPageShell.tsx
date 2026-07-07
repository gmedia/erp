import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { AlertCircle, RefreshCw } from 'lucide-react';
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
}: DashboardPageShellProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>{title}</title>
            </Helmet>
            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 pb-12 md:p-6">
                <div className="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-foreground">
                            {heading}
                        </h1>
                        <p className="mt-1 text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    <div className="flex flex-col items-start gap-3 sm:flex-row sm:items-center">
                        {toolbar}
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => refetch()}
                            disabled={isLoading}
                            className="flex items-center gap-2"
                        >
                            <RefreshCw
                                className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`}
                            />
                            Refresh Data
                        </Button>
                    </div>
                </div>

                {isError && (
                    <Alert variant="destructive" className="mb-4">
                        <AlertCircle className="h-4 w-4" />
                        <AlertTitle>{errorTitle}</AlertTitle>
                        <AlertDescription className="mt-2 max-w-lg text-sm">
                            {error instanceof Error
                                ? error.message
                                : errorMessage}
                        </AlertDescription>
                    </Alert>
                )}

                {children}
            </div>
        </AppLayout>
    );
}
