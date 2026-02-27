import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { RecentMaintenanceItem } from '../../hooks/useAssetDashboard';
import { Wrench, Calendar as CalendarIcon, ChevronRight } from 'lucide-react';
import { format } from 'date-fns';
import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';

interface RecentMaintenancesProps {
    data?: RecentMaintenanceItem[];
    isLoading: boolean;
}

export function RecentMaintenances({ data, isLoading }: RecentMaintenancesProps) {
    if (isLoading) {
        return (
            <Card className="flex flex-col h-full min-h-[300px]">
                <CardHeader className="pb-2">
                    <CardTitle className="flex items-center space-x-2">
                        <Skeleton className="h-5 w-5 rounded-full" />
                        <Skeleton className="h-5 w-40" />
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex-1 p-0">
                    <div className="divide-y">
                        {[...Array(4)].map((_, i) => (
                            <div key={i} className="p-4 flex flex-col space-y-2">
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
            <Card className="flex flex-col h-full min-h-[300px]">
                <CardHeader className="pb-2">
                    <CardTitle className="flex items-center space-x-2 text-muted-foreground">
                        <Wrench className="h-5 w-5" />
                        <span>Upcoming Maintenance</span>
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex flex-1 flex-col items-center justify-center text-center text-muted-foreground p-6">
                    <div className="rounded-full bg-muted p-3 mb-3">
                        <Wrench className="h-6 w-6 opacity-40" />
                    </div>
                    <p>No upcoming or in-progress maintenances.</p>
                </CardContent>
            </Card>
        );
    }

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'scheduled': return 'default';
            case 'in_progress': return 'warning';
            default: return 'outline';
        }
    };
    
    const getStatusLabel = (status: string) => {
        return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
    };

    return (
        <Card className="flex flex-col h-full min-h-[300px]">
            <CardHeader className="pb-2 flex flex-row items-center justify-between border-b">
                <CardTitle className="flex items-center space-x-2">
                    <Wrench className="h-5 w-5 text-muted-foreground" />
                    <span>Upcoming Maintenance</span>
                </CardTitle>
                <Link 
                    href="/asset-maintenances" 
                    className="text-xs flex items-center text-primary hover:underline font-medium"
                >
                    View All <ChevronRight className="ml-1 h-3 w-3" />
                </Link>
            </CardHeader>
            <CardContent className="flex-1 p-0">
                <div className="divide-y overflow-y-auto max-h-[320px]">
                    {data.map((maintenance) => {
                        const dateFormatted = maintenance.scheduled_at 
                            ? format(new Date(maintenance.scheduled_at), 'MMM dd, yyyy') 
                            : 'Not scheduled';
                            
                        return (
                            <div key={maintenance.id} className="p-4 hover:bg-muted/50 transition-colors">
                                <div className="flex justify-between items-start mb-2">
                                    <p className="font-medium text-sm leading-tight text-foreground line-clamp-1 pr-2">
                                        {maintenance.asset_name}
                                    </p>
                                    <Badge variant={getStatusColor(maintenance.status) as any} className="text-[10px] px-1.5 py-0">
                                        {getStatusLabel(maintenance.status)}
                                    </Badge>
                                </div>
                                
                                <div className="flex flex-wrap gap-y-1 gap-x-3 text-xs text-muted-foreground">
                                    <div className="flex items-center">
                                        <span className="font-mono text-[10px] bg-muted px-1 py-0.5 rounded text-muted-foreground border">
                                            {maintenance.asset_code}
                                        </span>
                                    </div>
                                    <div className="flex items-center capitalize">
                                        <Wrench className="mr-1 h-3 w-3 opacity-70" />
                                        {maintenance.maintenance_type.replace('_', ' ')}
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
