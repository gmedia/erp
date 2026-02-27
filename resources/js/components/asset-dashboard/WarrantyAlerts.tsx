import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { WarrantyAlertItem } from '../../hooks/useAssetDashboard';
import { AlertTriangle, Calendar, ChevronRight, ShieldCheck } from 'lucide-react';
import { format } from 'date-fns';
import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';

interface WarrantyAlertsProps {
    data?: WarrantyAlertItem[];
    isLoading: boolean;
}

export function WarrantyAlerts({ data, isLoading }: WarrantyAlertsProps) {
    if (isLoading) {
        return (
            <Card className="flex flex-col h-full min-h-[300px]">
                <CardHeader className="pb-2">
                    <CardTitle className="flex items-center space-x-2">
                        <Skeleton className="h-5 w-5 rounded-full" />
                        <Skeleton className="h-5 w-32" />
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex-1 p-0">
                    <div className="divide-y">
                        {[...Array(4)].map((_, i) => (
                            <div key={i} className="p-4 flex flex-col space-y-2">
                                <div className="flex justify-between">
                                    <Skeleton className="h-4 w-40" />
                                    <Skeleton className="h-5 w-16 xl:w-20 rounded-full" />
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
            <Card className="flex flex-col h-full min-h-[300px]">
                <CardHeader className="pb-2">
                    <CardTitle className="flex items-center space-x-2 text-muted-foreground">
                        <ShieldCheck className="h-5 w-5 text-emerald-500" />
                        <span>Expiring Warranties</span>
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex flex-1 flex-col items-center justify-center text-center text-muted-foreground p-6">
                    <div className="rounded-full bg-emerald-500/10 p-3 mb-3">
                        <ShieldCheck className="h-6 w-6 text-emerald-500" />
                    </div>
                    <p className="text-sm">All clear! No asset warranties are expiring within the next 30 days.</p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="flex flex-col h-full min-h-[300px] border-amber-200/50 shadow-sm">
            <CardHeader className="pb-2 flex flex-row items-center justify-between border-b bg-amber-50/50 dark:bg-amber-500/5">
                <CardTitle className="flex items-center space-x-2 text-amber-700 dark:text-amber-500">
                    <AlertTriangle className="h-5 w-5" />
                    <span>Expiring Warranties</span>
                </CardTitle>
            </CardHeader>
            <CardContent className="flex-1 p-0">
                <div className="divide-y overflow-y-auto max-h-[320px]">
                    {data.map((asset) => {
                        const isCritical = asset.days_remaining <= 7;
                        
                        return (
                            <div key={asset.id} className="p-4 hover:bg-muted/50 transition-colors">
                                <div className="flex justify-between items-start mb-1">
                                    <Link 
                                        href={`/assets/${asset.id}`}
                                        className="font-medium text-sm leading-tight text-foreground hover:text-primary hover:underline line-clamp-1 pr-2"
                                    >
                                        {asset.name}
                                    </Link>
                                    <Badge 
                                        variant={isCritical ? 'destructive' : 'secondary'} 
                                        className={`text-[10px] whitespace-nowrap min-w-fit px-1.5 py-0 font-medium ${!isCritical ? 'bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900/30 dark:text-amber-400' : ''}`}
                                    >
                                        {asset.days_remaining} {asset.days_remaining === 1 ? 'day' : 'days'} left
                                    </Badge>
                                </div>
                                
                                <div className="flex gap-3 text-xs text-muted-foreground mt-2">
                                    <div className="flex items-center">
                                        <span className="font-mono text-[10px] px-1 py-0.5 rounded border bg-muted/50">
                                            {asset.asset_code}
                                        </span>
                                    </div>
                                    <div className="flex items-center" title="Warranty End Date">
                                        <Calendar className="mr-1 h-3 w-3 opacity-70" />
                                        {format(new Date(asset.warranty_end_date), 'MMM dd, yyyy')}
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
