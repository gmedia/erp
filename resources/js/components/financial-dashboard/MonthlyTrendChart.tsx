import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/utils';
import { memo } from 'react';

export interface MonthlyTrendItem {
    month: number;
    label: string;
    revenue: number;
    expenses: number;
    net_income: number;
}

interface MonthlyTrendChartProps {
    readonly data?: MonthlyTrendItem[];
    readonly isLoading: boolean;
}

const PLACEHOLDER_BARS = [
    { month: 1, height: 40 },
    { month: 2, height: 70 },
    { month: 3, height: 55 },
    { month: 4, height: 85 },
    { month: 5, height: 60 },
    { month: 6, height: 75 },
    { month: 7, height: 50 },
    { month: 8, height: 90 },
    { month: 9, height: 65 },
    { month: 10, height: 80 },
    { month: 11, height: 45 },
    { month: 12, height: 70 },
] as const;

function formatCompact(value: number): string {
    const absValue = Math.abs(value);
    if (absValue === 0) return '0';
    if (absValue < 1000) return absValue.toString();
    if (absValue < 1000000) return `${(absValue / 1000).toFixed(1)}K`;
    return `${(absValue / 1000000).toFixed(1)}M`;
}

function netIncomeColorClass(value: number): string {
    if (value > 0) return 'text-emerald-600';
    if (value < 0) return 'text-rose-600';
    return 'text-muted-foreground';
}

export const MonthlyTrendChart = memo<MonthlyTrendChartProps>(
    function MonthlyTrendChart({ data, isLoading }) {
        if (isLoading) {
            return (
                <Card className="flex h-[400px] w-full items-center justify-center">
                    <div className="flex flex-col items-center space-y-4">
                        <div className="flex h-40 items-end space-x-2 opacity-20">
                            {PLACEHOLDER_BARS.map(({ month, height }) => (
                                <div
                                    key={`trend-placeholder-${month}`}
                                    className="flex w-8 items-end space-x-0.5"
                                >
                                    <div
                                        className="w-3.5 animate-pulse rounded-t bg-muted-foreground"
                                        style={{ height: `${height}%` }}
                                    ></div>
                                    <div
                                        className="w-3.5 animate-pulse rounded-t bg-muted-foreground"
                                        style={{ height: `${height * 0.7}%` }}
                                    ></div>
                                </div>
                            ))}
                        </div>
                        <div className="text-sm text-muted-foreground">
                            Loading trends...
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
                    No trend data available.
                </Card>
            );
        }

        const maxValue = Math.max(
            ...data.map((d) => Math.max(d.revenue, d.expenses)),
            1,
        );

        return (
            <Card className="w-full">
                <CardHeader className="pb-2">
                    <div className="flex items-center justify-between">
                        <CardTitle>Revenue vs Expenses</CardTitle>
                        <div className="flex items-center space-x-4 text-sm">
                            <div className="flex items-center space-x-2">
                                <div className="h-3 w-3 rounded-full bg-emerald-500"></div>
                                <span className="text-muted-foreground">
                                    Revenue
                                </span>
                            </div>
                            <div className="flex items-center space-x-2">
                                <div className="h-3 w-3 rounded-full bg-rose-500"></div>
                                <span className="text-muted-foreground">
                                    Expenses
                                </span>
                            </div>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="pb-4">
                    <div className="overflow-x-auto">
                        <div className="flex min-w-max items-end justify-between space-x-4 px-2">
                            {data.map((item) => {
                                const revenueHeight = Math.max(
                                    (item.revenue / maxValue) * 160,
                                    2,
                                );
                                const expensesHeight = Math.max(
                                    (item.expenses / maxValue) * 160,
                                    2,
                                );

                                const netIncomeColor = netIncomeColorClass(
                                    item.net_income,
                                );

                                return (
                                    <div
                                        key={item.month}
                                        className="flex flex-col items-center space-y-2"
                                    >
                                        <div className="flex h-[160px] items-end space-x-1">
                                            <div
                                                className="group relative w-6 rounded-t bg-emerald-500 transition-all hover:opacity-80"
                                                style={{
                                                    height: `${revenueHeight}px`,
                                                }}
                                                title={`Revenue: ${formatCurrency(item.revenue)}`}
                                            >
                                                <div className="absolute -top-8 left-1/2 hidden -translate-x-1/2 rounded bg-gray-900 px-2 py-1 text-xs whitespace-nowrap text-white group-hover:block">
                                                    {formatCurrency(
                                                        item.revenue,
                                                    )}
                                                </div>
                                            </div>
                                            <div
                                                className="group relative w-6 rounded-t bg-rose-500 transition-all hover:opacity-80"
                                                style={{
                                                    height: `${expensesHeight}px`,
                                                }}
                                                title={`Expenses: ${formatCurrency(item.expenses)}`}
                                            >
                                                <div className="absolute -top-8 left-1/2 hidden -translate-x-1/2 rounded bg-gray-900 px-2 py-1 text-xs whitespace-nowrap text-white group-hover:block">
                                                    {formatCurrency(
                                                        item.expenses,
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex flex-col items-center space-y-1">
                                            <span className="text-xs font-medium text-muted-foreground">
                                                {item.label}
                                            </span>
                                            <span
                                                className={`text-xs font-semibold ${netIncomeColor}`}
                                            >
                                                {formatCompact(item.net_income)}
                                            </span>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </CardContent>
            </Card>
        );
    },
);
