import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Skeleton } from '@/components/ui/skeleton';
import { format } from 'date-fns';
import { AlertTriangle, Calendar, ShieldCheck } from 'lucide-react';
import { Link } from 'react-router-dom';
import { WarrantyAlertItem } from '../../hooks/useAssetDashboard';

interface WarrantyAlertsProps {
    data?: WarrantyAlertItem[];
    isLoading: boolean;
}

export function WarrantyAlerts({
    data,
    isLoading,
}: Readonly<WarrantyAlertsProps>) {
    if (isLoading) {
        return (
            <Card className="flex h-full min-h-[300px] flex-col">
                <CardHeader className="pb-2">
                    <CardTitle className="flex items-center space-x-2">
                        <Skeleton className="h-5 w-5 rounded-full" />
                        <Skeleton className="h-5 w-32" />
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex-1 p-0">
                    <div className="divide-y">
                        {[...Array(4)].map((_, i) => (
                            <div
                                key={i}
                                className="flex flex-col space-y-2 p-4"
                            >
                                <div className="flex justify-between">
                                    <Skeleton className="h-4 w-40" />
                                    <Skeleton className="h-5 w-16 rounded-full xl:w-20" />
                                </div>
                                <Skeleton className="h-3 w-28" />
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
                        <ShieldCheck className="h-5 w-5 text-emerald-500" />
                        <span>Expiring Warranties</span>
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex flex-1 flex-col items-center justify-center p-6 text-center text-muted-foreground">
                    <div className="mb-3 rounded-full bg-emerald-500/10 p-3">
                        <ShieldCheck className="h-6 w-6 text-emerald-500" />
                    </div>
                    <p className="text-sm">
                        All clear! No asset warranties are expiring within the
                        next 30 days.
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="flex h-full min-h-[300px] flex-col border-amber-200/50 shadow-sm">
            <CardHeader className="flex flex-row items-center justify-between border-b bg-amber-50/50 pb-2 dark:bg-amber-500/5">
                <CardTitle className="flex items-center space-x-2 text-amber-700 dark:text-amber-500">
                    <AlertTriangle className="h-5 w-5" />
                    <span>Expiring Warranties</span>
                </CardTitle>
            </CardHeader>
            <CardContent className="flex-1 p-0">
                <ScrollArea className="max-h-[320px]">
                    <div className="divide-y pr-4">
                        {data.map((asset) => {
                            const isCritical = asset.days_remaining <= 7;
                            const warningBadgeClassName = isCritical
                                ? ''
                                : 'bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-400';

                            return (
                                <div
                                    key={asset.id}
                                    className="p-4 transition-colors hover:bg-muted/50"
                                >
                                    <div className="mb-1 flex items-start justify-between">
                                        <Link
                                            to={`/assets/${asset.id}`}
                                            className="line-clamp-1 pr-2 text-sm leading-tight font-medium text-foreground hover:text-primary hover:underline"
                                        >
                                            {asset.name}
                                        </Link>
                                        <Badge
                                            variant={
                                                isCritical
                                                    ? 'destructive'
                                                    : 'secondary'
                                            }
                                            className={`min-w-fit px-1.5 py-0 text-[10px] font-medium whitespace-nowrap ${warningBadgeClassName}`}
                                        >
                                            {asset.days_remaining}{' '}
                                            {asset.days_remaining === 1
                                                ? 'day'
                                                : 'days'}{' '}
                                            left
                                        </Badge>
                                    </div>

                                    <div className="mt-2 flex gap-3 text-xs text-muted-foreground">
                                        <div className="flex items-center">
                                            <span className="rounded border bg-muted/50 px-1 py-0.5 font-mono text-[10px]">
                                                {asset.asset_code}
                                            </span>
                                        </div>
                                        <div
                                            className="flex items-center"
                                            title="Warranty End Date"
                                        >
                                            <Calendar className="mr-1 h-3 w-3 opacity-70" />
                                            {format(
                                                new Date(
                                                    asset.warranty_end_date,
                                                ),
                                                'MMM dd, yyyy',
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </ScrollArea>
            </CardContent>
        </Card>
    );
}
