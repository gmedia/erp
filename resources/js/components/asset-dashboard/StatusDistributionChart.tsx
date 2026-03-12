import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { StatusDistributionItem } from '../../hooks/useAssetDashboard';

interface StatusDistributionChartProps {
    readonly data?: StatusDistributionItem[];
    readonly isLoading: boolean;
}

export function StatusDistributionChart({
    data,
    isLoading,
}: StatusDistributionChartProps) {
    if (isLoading) {
        return (
            <Card className="h-[400px] w-full animate-pulse">
                <CardHeader className="pb-2">
                    <CardTitle className="w-48 rounded bg-muted text-transparent">
                        Asset Status Distribution
                    </CardTitle>
                </CardHeader>
                <CardContent className="flex h-64 items-center justify-center">
                    <div className="h-32 w-32 animate-spin rounded-full border-8 border-muted border-t-muted-foreground/30" />
                </CardContent>
            </Card>
        );
    }

    if (!data || data.length === 0) {
        return (
            <Card className="flex h-[400px] w-full flex-col items-center justify-center p-6 text-center text-muted-foreground">
                <div className="mb-4 rounded-full bg-muted p-4">
                    <svg
                        className="h-8 w-8 opacity-50"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"
                        />
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"
                        />
                    </svg>
                </div>
                No status distribution data available.
            </Card>
        );
    }

    const total = data.reduce((acc, curr) => acc + curr.count, 0);
    const activeData = data.filter((item) => item.count > 0);

    // Generate conic gradient string for the donut chart
    let currentPercentage = 0;
    const gradientStops = activeData
        .map((item) => {
            const percentage = (item.count / total) * 100;
            const stop = `${item.color} ${currentPercentage}% ${currentPercentage + percentage}%`;
            currentPercentage += percentage;
            return stop;
        })
        .join(', ');

    return (
        <Card className="flex h-[400px] w-full flex-col">
            <CardHeader className="pb-2">
                <CardTitle>Asset Status Distribution</CardTitle>
                <CardDescription>
                    Visual breakdown of {total} assets
                </CardDescription>
            </CardHeader>
            <CardContent className="flex flex-1 flex-col items-center gap-6 py-4">
                {/* CSS Donut Chart */}
                <div className="relative h-40 w-40 flex-shrink-0 drop-shadow-md">
                    <div
                        className="h-full w-full rounded-full transition-all duration-500 ease-in-out"
                        style={{
                            background: `conic-gradient(${gradientStops})`,
                        }}
                    />
                    {/* Inner circle for donut hole */}
                    <div className="absolute inset-0 z-10 m-auto flex h-28 w-28 flex-col items-center justify-center rounded-full bg-card shadow-inner">
                        <span className="text-3xl font-bold">{total}</span>
                        <span className="mt-1 text-xs tracking-wider text-muted-foreground">
                            Total
                        </span>
                    </div>
                </div>

                {/* Legend */}
                <div className="mt-2 flex w-full flex-wrap items-center justify-center gap-x-4 gap-y-2">
                    {activeData.map((item) => {
                        const percentage = Math.round(
                            (item.count / total) * 100,
                        );
                        return (
                            <div
                                key={item.id}
                                className="flex items-center gap-2 text-sm"
                            >
                                <span
                                    className="h-3 w-3 flex-shrink-0 rounded-full"
                                    style={{ backgroundColor: item.color }}
                                />
                                <span className="mr-1 font-medium text-muted-foreground">
                                    {item.name}
                                </span>
                                <span className="font-bold">{percentage}%</span>
                            </div>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}
