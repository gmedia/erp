import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { format } from 'date-fns';
import { Calendar as CalendarIcon, ChevronRight, Wrench } from 'lucide-react';
import { Link } from 'react-router-dom';
import { RecentMaintenanceItem } from '../../hooks/useAssetDashboard';

const MAINTENANCE_SKELETON_ROWS = [1, 2, 3, 4] as const;

interface RecentMaintenancesProps {
    readonly data?: RecentMaintenanceItem[];
    readonly isLoading: boolean;
}

export function RecentMaintenances({
    data,
    isLoading,
}: RecentMaintenancesProps) {
    if (isLoading) {
        return (
            <Card className="flex h-full min-h-[300px] flex-col">
                <CardHeader className="pb-2">
                    <CardTitle className="flex items-center space-x-2">
                        <Skeleton className="h-5 w-5 rounded-full" />
                        <Skeleton className="h-5 w-40" />
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex-1 p-0">
                    <div className="divide-y">
                        {MAINTENANCE_SKELETON_ROWS.map((rowNumber) => (
                            <div
                                key={`maintenance-skeleton-${rowNumber}`}
                                className="flex flex-col space-y-2 p-4"
                            >
                                <div className="flex justify-between">
                                    <Skeleton className="h-4 w-32" />
                                    <Skeleton className="h-5 w-20 rounded-full" />
                                </div>
                                <div className="flex space-x-4">
                                    <Skeleton className="h-3 w-24" />
                                    <Skeleton className="h-3 w-32" />
                                </div>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        );
    }

    if (!data || data.length === 0) {
        return (
            <Card className="flex h-full min-h-[300px] flex-col">
                <CardHeader className="pb-2">
                    <CardTitle className="flex items-center space-x-2 text-muted-foreground">
                        <Wrench className="h-5 w-5" />
                        <span>Upcoming Maintenance</span>
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex flex-1 flex-col items-center justify-center p-6 text-center text-muted-foreground">
                    <div className="mb-3 rounded-full bg-muted p-3">
                        <Wrench className="h-6 w-6 opacity-40" />
                    </div>
                    <p>No upcoming or in-progress maintenances.</p>
                </CardContent>
            </Card>
        );
    }

    const getStatusColor = (
        status: string,
    ): 'default' | 'secondary' | 'outline' => {
        switch (status) {
            case 'scheduled':
                return 'default';
            case 'in_progress':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    const getStatusLabel = (status: string) => {
        return status
            .split('_')
            .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    };

    return (
        <Card className="flex h-full min-h-[300px] flex-col">
            <CardHeader className="flex flex-row items-center justify-between border-b pb-2">
                <CardTitle className="flex items-center space-x-2">
                    <Wrench className="h-5 w-5 text-muted-foreground" />
                    <span>Upcoming Maintenance</span>
                </CardTitle>
                <Link
                    to="/asset-maintenances"
                    className="flex items-center text-xs font-medium text-primary hover:underline"
                >
                    View All <ChevronRight className="ml-1 h-3 w-3" />
                </Link>
            </CardHeader>
            <CardContent className="flex-1 p-0">
                <div className="max-h-[320px] divide-y overflow-y-auto">
                    {data.map((maintenance) => {
                        const dateFormatted = maintenance.scheduled_at
                            ? format(
                                  new Date(maintenance.scheduled_at),
                                  'MMM dd, yyyy',
                              )
                            : 'Not scheduled';

                        return (
                            <div
                                key={maintenance.id}
                                className="p-4 transition-colors hover:bg-muted/50"
                            >
                                <div className="mb-2 flex items-start justify-between">
                                    <p className="line-clamp-1 pr-2 text-sm leading-tight font-medium text-foreground">
                                        {maintenance.asset_name}
                                    </p>
                                    <Badge
                                        variant={getStatusColor(
                                            maintenance.status,
                                        )}
                                        className="px-1.5 py-0 text-[10px]"
                                    >
                                        {getStatusLabel(maintenance.status)}
                                    </Badge>
                                </div>

                                <div className="flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                    <div className="flex items-center">
                                        <span className="rounded border bg-muted px-1 py-0.5 font-mono text-[10px] text-muted-foreground">
                                            {maintenance.asset_code}
                                        </span>
                                    </div>
                                    <div className="flex items-center capitalize">
                                        <Wrench className="mr-1 h-3 w-3 opacity-70" />
                                        {maintenance.maintenance_type.replace(
                                            '_',
                                            ' ',
                                        )}
                                    </div>
                                    <div className="flex items-center">
                                        <CalendarIcon className="mr-1 h-3 w-3 opacity-70" />
                                        {dateFormatted}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}
