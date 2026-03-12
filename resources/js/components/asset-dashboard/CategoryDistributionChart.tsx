import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CategoryDistributionItem } from '../../hooks/useAssetDashboard';

const CATEGORY_PLACEHOLDER_HEIGHTS = [40, 70, 45, 90, 60] as const;

interface CategoryDistributionChartProps {
    readonly data?: CategoryDistributionItem[];
    readonly isLoading: boolean;
}

export function CategoryDistributionChart({
    data,
    isLoading,
}: CategoryDistributionChartProps) {
    if (isLoading) {
        return (
            <Card className="flex h-[400px] w-full items-center justify-center p-6 sm:p-8">
                <div className="flex flex-col items-center space-y-4">
                    <div className="flex h-32 w-48 items-end space-x-2 opacity-20">
                        {CATEGORY_PLACEHOLDER_HEIGHTS.map((height) => (
                            <div
                                key={`chart-placeholder-${height}`}
                                className="w-8 animate-pulse rounded-t-sm bg-muted-foreground"
                                style={{ height: `${height}%` }}
                            ></div>
                        ))}
                    </div>
                    <div className="text-sm text-muted-foreground">
                        Loading chart...
                    </div>
                </div>
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
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        />
                    </svg>
                </div>
                No category distribution data available.
            </Card>
        );
    }

    const maxCount = Math.max(...data.map((d) => d.count), 1);

    return (
        <Card className="flex h-[400px] w-full flex-col">
            <CardHeader className="pb-2">
                <CardTitle>Top Asset Categories</CardTitle>
            </CardHeader>
            <CardContent className="flex-1 overflow-hidden pb-4">
                <div className="flex h-full w-full flex-col justify-center space-y-4 pr-4">
                    {data.map((item) => {
                        const widthStr = `${Math.max((item.count / maxCount) * 100, 2)}%`;
                        return (
                            <div
                                key={item.name}
                                className="flex flex-col space-y-1"
                            >
                                <div className="flex items-center justify-between text-sm">
                                    <span className="truncate pr-4 font-medium text-muted-foreground">
                                        {item.name}
                                    </span>
                                    <span className="font-bold tabular-nums">
                                        {item.count}
                                    </span>
                                </div>
                                <div className="h-4 w-full overflow-hidden rounded bg-secondary">
                                    <div
                                        className="h-full rounded-r bg-blue-500 transition-all duration-500"
                                        style={{ width: widthStr }}
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
