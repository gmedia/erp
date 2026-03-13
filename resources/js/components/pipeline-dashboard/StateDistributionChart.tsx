import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { StateSummary } from '@/hooks/usePipelineDashboard';

interface StateDistributionChartProps {
    readonly data: StateSummary[];
    readonly isLoading: boolean;
}

export function StateDistributionChart({
    data,
    isLoading,
}: StateDistributionChartProps) {
    if (isLoading) {
        return (
            <Card className="h-[350px] animate-pulse">
                <CardHeader>
                    <CardTitle className="w-48 rounded bg-muted text-transparent">
                        Loading Chart
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex h-48 items-center justify-center">
                    <div className="h-32 w-32 animate-spin rounded-full border-8 border-muted border-t-muted-foreground/30" />
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
                    <CardDescription>
                        Entities mapped by pipeline state
                    </CardDescription>
                </CardHeader>
                <CardContent className="flex h-[200px] items-center justify-center">
                    <p className="text-muted-foreground">No entities found.</p>
                </CardContent>
            </Card>
        );
    }

    // Generate conic gradient string for the donut chart
    let currentPercentage = 0;
    const gradientStops = data
        .filter((item) => item.count > 0)
        .map((item) => {
            const percentage = (item.count / total) * 100;
            const stop = `${item.color} ${currentPercentage}% ${currentPercentage + percentage}%`;
            currentPercentage += percentage;
            return stop;
        })
        .join(', ');

    return (
        <Card className="flex h-full flex-col overflow-hidden">
            <CardHeader className="pb-2">
                <CardTitle>State Distribution</CardTitle>
                <CardDescription>
                    Visual breakdown of {total} entities
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-1 flex-col items-center gap-6 py-4">
                {/* CSS Donut Chart */}
                <div className="relative h-36 w-36 flex-shrink-0 drop-shadow-md">
                    <div
                        className="h-full w-full rounded-full transition-all duration-500 ease-in-out"
                        style={{
                            background: `conic-gradient(${gradientStops})`,
                        }}
                    />
                    {/* Inner circle for donut hole */}
                    <div className="absolute inset-0 z-10 m-auto flex h-24 w-24 flex-col items-center justify-center rounded-full bg-card shadow-inner">
                        <span className="text-2xl font-bold">{total}</span>
                        <span className="text-[10px] tracking-wider text-muted-foreground uppercase">
                            Total
                        </span>
                    </div>
                </div>

                {/* Legend */}
                <ScrollArea className="max-h-40 w-full">
                    <div className="flex flex-col gap-1.5 pr-4">
                        {data
                            .filter((item) => item.count > 0)
                            .map((item) => (
                                <div
                                    key={item.state_id}
                                    className="flex items-center justify-between text-xs"
                                >
                                    <div className="flex min-w-0 items-center gap-2">
                                        <span
                                            className="h-2.5 w-2.5 flex-shrink-0 rounded-full ring-1 ring-border"
                                            style={{ backgroundColor: item.color }}
                                        />
                                        <span
                                            className="truncate font-medium"
                                            title={item.name}
                                        >
                                            {item.name}
                                        </span>
                                    </div>
                                    <span className="ml-3 flex-shrink-0 text-muted-foreground tabular-nums">
                                        {Math.round((item.count / total) * 100)}%
                                    </span>
                                </div>
                            ))}
                    </div>
                </ScrollArea>
            </CardContent>
        </Card>
    );
}
