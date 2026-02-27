import React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { StateSummary } from '@/hooks/usePipelineDashboard';

interface StateDistributionChartProps {
    data: StateSummary[];
    isLoading: boolean;
}

export function StateDistributionChart({ data, isLoading }: StateDistributionChartProps) {
    if (isLoading) {
        return (
            <Card className="h-[350px] animate-pulse">
                <CardHeader>
                    <CardTitle className="text-transparent bg-muted rounded w-48">Loading Chart</CardTitle>
                </CardHeader>
                <CardContent className="flex items-center justify-center h-48">
                    <div className="h-32 w-32 rounded-full border-8 border-muted border-t-muted-foreground/30 animate-spin" />
                </CardContent>
            </Card>
        );
    }

    const total = data.reduce((acc, curr) => acc + curr.count, 0);

    if (total === 0) {
        return (
            <Card className="h-full">
                <CardHeader>
                    <CardTitle>State Distribution</CardTitle>
                    <CardDescription>Entities mapped by pipeline state</CardDescription>
                </CardHeader>
                <CardContent className="flex items-center justify-center h-[200px]">
                    <p className="text-muted-foreground">No entities found.</p>
                </CardContent>
            </Card>
        );
    }

    // Generate conic gradient string for the donut chart
    let currentPercentage = 0;
    const gradientStops = data
        .filter(item => item.count > 0)
        .map(item => {
            const percentage = (item.count / total) * 100;
            const stop = `${item.color} ${currentPercentage}% ${currentPercentage + percentage}%`;
            currentPercentage += percentage;
            return stop;
        })
        .join(', ');

    return (
        <Card className="h-full flex flex-col overflow-hidden">
            <CardHeader className="pb-2">
                <CardTitle>State Distribution</CardTitle>
                <CardDescription>Visual breakdown of {total} entities</CardDescription>
            </CardHeader>
            <CardContent className="flex-1 flex flex-col items-center gap-6 py-4">
                {/* CSS Donut Chart */}
                <div className="relative w-36 h-36 flex-shrink-0 drop-shadow-md">
                    <div 
                        className="w-full h-full rounded-full transition-all duration-500 ease-in-out"
                        style={{ background: `conic-gradient(${gradientStops})` }}
                    />
                    {/* Inner circle for donut hole */}
                    <div className="absolute inset-0 m-auto w-24 h-24 bg-card rounded-full shadow-inner z-10 flex items-center justify-center flex-col">
                        <span className="text-2xl font-bold">{total}</span>
                        <span className="text-[10px] text-muted-foreground uppercase tracking-wider">Total</span>
                    </div>
                </div>

                {/* Legend */}
                <div className="flex flex-col gap-1.5 w-full max-h-40 overflow-y-auto">
                    {data.filter(item => item.count > 0).map((item) => (
                        <div key={item.state_id} className="flex items-center justify-between text-xs">
                            <div className="flex items-center gap-2 min-w-0">
                                <span 
                                    className="w-2.5 h-2.5 rounded-full flex-shrink-0 ring-1 ring-border" 
                                    style={{ backgroundColor: item.color }} 
                                />
                                <span className="font-medium truncate" title={item.name}>{item.name}</span>
                            </div>
                            <span className="text-muted-foreground ml-3 tabular-nums flex-shrink-0">
                                {Math.round((item.count / total) * 100)}%
                            </span>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}
