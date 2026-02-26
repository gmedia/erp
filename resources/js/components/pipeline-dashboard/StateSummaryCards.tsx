import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { StateSummary } from '@/hooks/usePipelineDashboard';

interface StateSummaryCardsProps {
    data: StateSummary[];
    isLoading: boolean;
}

export function StateSummaryCards({ data, isLoading }: StateSummaryCardsProps) {
    if (isLoading) {
        return (
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                {[...Array(4)].map((_, i) => (
                    <Card key={i} className="animate-pulse">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-transparent bg-gray-200 rounded">
                                Loading State...
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-transparent bg-gray-200 rounded w-12 h-8">0</div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        );
    }

    if (!data || data.length === 0) {
        return (
            <div className="w-full rounded-lg border border-dashed p-8 text-center text-gray-500">
                No state data available for the selected pipeline.
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            {data.map((state) => (
                <Card key={state.state_id} className="overflow-hidden transition-all hover:shadow-md">
                    <div className="h-2 w-full" style={{ backgroundColor: state.color }} />
                    <CardHeader className="flex flex-row items-center justify-between pb-2 pt-4">
                        <CardTitle className="text-sm font-medium text-gray-600">
                            {state.name}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-3xl font-bold">{state.count}</div>
                        <p className="text-xs text-muted-foreground mt-1">
                            entities currently in state
                        </p>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
