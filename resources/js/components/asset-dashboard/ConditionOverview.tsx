import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { ConditionOverviewItem } from '../../hooks/useAssetDashboard';
import { ClipboardCheck } from 'lucide-react';

interface ConditionOverviewProps {
    data?: ConditionOverviewItem[];
    isLoading: boolean;
}

export function ConditionOverview({ data, isLoading }: ConditionOverviewProps) {
    if (isLoading) {
        return (
            <Card className="flex flex-col h-full min-h-[300px]">
                <CardHeader className="pb-2">
                    <CardTitle className="flex items-center space-x-2">
                        <Skeleton className="h-5 w-5 rounded-full" />
                        <Skeleton className="h-5 w-32" />
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex-1">
                    <div className="flex flex-col space-y-4 pt-4">
                        {[...Array(3)].map((_, i) => (
                            <div key={i} className="flex justify-between items-center">
                                <div className="flex items-center space-x-3">
                                    <Skeleton className="h-3 w-3 rounded-full" />
                                    <Skeleton className="h-4 w-24" />
                                </div>
                                <Skeleton className="h-5 w-12" />
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
                    <CardTitle className="flex items-center space-x-2">
                        <ClipboardCheck className="h-5 w-5 text-muted-foreground" />
                        <span>Condition Overview</span>
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex flex-1 items-center justify-center text-center text-muted-foreground">
                    No condition data available.
                </CardContent>
            </Card>
        );
    }

    const totalCount = data.reduce((sum, item) => sum + item.count, 0);

    return (
        <Card className="flex flex-col h-full min-h-[300px]">
            <CardHeader className="pb-2">
                <CardTitle className="flex items-center space-x-2">
                    <ClipboardCheck className="h-5 w-5 text-muted-foreground" />
                    <span>Condition Overview</span>
                </CardTitle>
            </CardHeader>
            <CardContent className="flex-1">
                <div className="flex flex-col space-y-5 pt-4">
                    {data.map((condition) => {
                        const percentage = totalCount > 0 ? Math.round((condition.count / totalCount) * 100) : 0;
                        
                        return (
                            <div key={condition.id} className="flex flex-col space-y-2">
                                <div className="flex justify-between items-center">
                                    <div className="flex items-center space-x-2">
                                        <div 
                                            className="h-3 w-3 rounded-full flex-shrink-0" 
                                            style={{ backgroundColor: condition.color }}
                                        />
                                        <span className="text-sm font-medium text-foreground">
                                            {condition.name}
                                        </span>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <span className="text-sm font-bold">{condition.count}</span>
                                        <span className="text-xs text-muted-foreground w-8 text-right">
                                            {percentage}%
                                        </span>
                                    </div>
                                </div>
                                <div className="h-2 w-full bg-secondary rounded-full overflow-hidden">
                                    <div 
                                        className="h-full rounded-full transition-all duration-500 ease-in-out" 
                                        style={{ 
                                            width: `${percentage}%`,
                                            backgroundColor: condition.color 
                                        }}
                                    />
                                </div>
                            </div>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}
