import React from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { StatusDistributionItem } from '../../hooks/useAssetDashboard';

interface StatusDistributionChartProps {
    data?: StatusDistributionItem[];
    isLoading: boolean;
}

export function StatusDistributionChart({ data, isLoading }: StatusDistributionChartProps) {
    if (isLoading) {
        return (
            <Card className="h-[400px] w-full animate-pulse">
                <CardHeader className="pb-2">
                    <CardTitle className="text-transparent bg-muted rounded w-48">Asset Status Distribution</CardTitle>
                </CardHeader>
                <CardContent className="flex items-center justify-center h-64">
                    <div className="h-32 w-32 rounded-full border-8 border-muted border-t-muted-foreground/30 animate-spin" />
                </CardContent>
            </Card>
        );
    }

    if (!data || data.length === 0) {
        return (
            <Card className="h-[400px] w-full flex flex-col items-center justify-center p-6 text-center text-muted-foreground">
                <div className="mb-4 rounded-full bg-muted p-4">
                    <svg className="h-8 w-8 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                </div>
                No status distribution data available.
            </Card>
        );
    }

    const total = data.reduce((acc, curr) => acc + curr.count, 0);
    const activeData = data.filter(item => item.count > 0);

    // Generate conic gradient string for the donut chart
    let currentPercentage = 0;
    const gradientStops = activeData.map(item => {
        const percentage = (item.count / total) * 100;
        const stop = `${item.color} ${currentPercentage}% ${currentPercentage + percentage}%`;
        currentPercentage += percentage;
        return stop;
    }).join(', ');

    return (
        <Card className="h-[400px] w-full flex flex-col">
            <CardHeader className="pb-2">
                <CardTitle>Asset Status Distribution</CardTitle>
                <CardDescription>Visual breakdown of {total} assets</CardDescription>
            </CardHeader>
            <CardContent className="flex-1 flex flex-col items-center gap-6 py-4">
                {/* CSS Donut Chart */}
                <div className="relative w-40 h-40 flex-shrink-0 drop-shadow-md">
                    <div 
                        className="w-full h-full rounded-full transition-all duration-500 ease-in-out"
                        style={{ background: `conic-gradient(${gradientStops})` }}
                    />
                    {/* Inner circle for donut hole */}
                    <div className="absolute inset-0 m-auto w-28 h-28 bg-card rounded-full shadow-inner z-10 flex items-center justify-center flex-col">
                        <span className="text-3xl font-bold">{total}</span>
                        <span className="text-xs text-muted-foreground tracking-wider mt-1">Total</span>
                    </div>
                </div>

                {/* Legend */}
                <div className="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 w-full mt-2">
                    {activeData.map((item) => {
                        const percentage = Math.round((item.count / total) * 100);
                        return (
                            <div key={item.id} className="flex items-center text-sm gap-2">
                                <span 
                                    className="w-3 h-3 rounded-full flex-shrink-0" 
                                    style={{ backgroundColor: item.color }} 
                                />
                                <span className="font-medium text-muted-foreground mr-1">{item.name}</span>
                                <span className="font-bold">{percentage}%</span>
                            </div>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}
