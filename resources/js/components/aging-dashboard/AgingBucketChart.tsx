import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { formatCurrency } from '@/lib/utils';
import { FileText } from 'lucide-react';
import { memo } from 'react';
import type { AgingBucket } from '../../hooks/useAgingDashboard';

interface AgingBucketChartProps {
    readonly title: string;
    readonly buckets: AgingBucket[];
    readonly totalOutstanding: number;
    readonly isLoading?: boolean;
    readonly accentColor: 'emerald' | 'rose';
}

// "Current" bukan tunggakan: netral. Intensitas warna baru meningkat pada
// bucket yang benar-benar jatuh tempo (W5-12).
const AGING_BAR_COLORS = [
    'bg-muted-foreground/30',
    'bg-warning/50',
    'bg-warning/70',
    'bg-destructive-foreground/70',
    'bg-destructive-foreground',
];

export const AgingBucketChart = memo<AgingBucketChartProps>(
    function AgingBucketChart({
        title,
        buckets,
        totalOutstanding,
        isLoading,
        accentColor,
    }) {
        if (isLoading) {
            return (
                <Card className="flex h-[320px] w-full flex-col">
                    <CardHeader className="pb-2">
                        <Skeleton className="h-6 w-48" />
                        <Skeleton className="mt-1 h-4 w-32" />
                    </CardHeader>
                    <CardContent className="flex-1 space-y-3">
                        {[1, 2, 3, 4, 5].map((i) => (
                            <Skeleton key={i} className="h-8 w-full" />
                        ))}
                    </CardContent>
                </Card>
            );
        }

        if (totalOutstanding === 0) {
            return (
                <Card className="flex h-[320px] w-full flex-col items-center justify-center p-6 text-center text-muted-foreground">
                    <div className="mb-4 rounded-full bg-muted p-4">
                        <FileText className="h-8 w-8 opacity-50" />
                    </div>
                    No outstanding {accentColor === 'emerald' ? 'AR' : 'AP'} as
                    of this date.
                </Card>
            );
        }

        return (
            <Card className="flex h-[320px] w-full flex-col">
                <CardHeader className="pb-2">
                    <CardTitle>{title}</CardTitle>
                    <p className="text-sm text-muted-foreground">
                        Total: {formatCurrency(totalOutstanding)}
                    </p>
                </CardHeader>
                <CardContent className="flex-1 space-y-3 pb-4">
                    {buckets.map((bucket, index) => {
                        const widthPercentage = Math.max(
                            bucket.amount > 0 ? 1 : 0,
                            Math.min(bucket.percentage, 100),
                        );
                        const barColor =
                            AGING_BAR_COLORS[index] ?? AGING_BAR_COLORS[4];

                        return (
                            <div
                                key={bucket.label}
                                className="flex flex-col space-y-1"
                            >
                                <div className="flex items-center justify-between text-sm">
                                    <span className="font-medium text-muted-foreground">
                                        {bucket.label}
                                    </span>
                                    <div className="flex items-center gap-2">
                                        <span className="font-bold tabular-nums">
                                            {formatCurrency(bucket.amount)}
                                        </span>
                                        <span className="text-xs text-muted-foreground">
                                            ({bucket.percentage.toFixed(1)}%)
                                        </span>
                                    </div>
                                </div>
                                <div className="h-3 w-full overflow-hidden rounded bg-secondary">
                                    <div
                                        className={`h-full rounded-r transition-all duration-500 ${barColor}`}
                                        style={{
                                            width: `${widthPercentage}%`,
                                        }}
                                    />
                                </div>
                            </div>
                        );
                    })}
                </CardContent>
            </Card>
        );
    },
);
