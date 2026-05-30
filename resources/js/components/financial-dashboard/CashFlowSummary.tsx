import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/utils';
import { memo } from 'react';
import type { CashFlowSummary as CashFlowSummaryType } from '../../hooks/useFinancialDashboard';

interface CashFlowSummaryProps {
    readonly data?: CashFlowSummaryType;
    readonly isLoading: boolean;
}

export const CashFlowSummary = memo<CashFlowSummaryProps>(
    function CashFlowSummary({ data, isLoading }) {
        if (isLoading) {
            return (
                <Card className="flex h-[300px] w-full items-center justify-center">
                    <div className="flex flex-col items-center space-y-4">
                        <div className="flex h-24 w-full flex-col space-y-3 px-8 opacity-20">
                            {[60, 80, 40].map((width, idx) => (
                                <div
                                    key={`cash-flow-placeholder-${idx}`}
                                    className="h-6 animate-pulse rounded bg-muted-foreground"
                                    style={{ width: `${width}%` }}
                                ></div>
                            ))}
                        </div>
                        <div className="text-sm text-muted-foreground">
                            Loading cash flow...
                        </div>
                    </div>
                </Card>
            );
        }

        if (!data) {
            return (
                <Card className="flex h-[300px] w-full flex-col items-center justify-center p-6 text-center text-muted-foreground">
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
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    No cash flow data available.
                </Card>
            );
        }

        const maxValue = Math.max(
            Math.abs(data.inflow),
            Math.abs(data.outflow),
            Math.abs(data.net),
            1,
        );

        const bars = [
            {
                label: 'Cash Inflow',
                value: data.inflow,
                color: 'bg-emerald-500',
            },
            {
                label: 'Cash Outflow',
                value: data.outflow,
                color: 'bg-rose-500',
            },
            {
                label: 'Net Cash Flow',
                value: data.net,
                color: data.net >= 0 ? 'bg-blue-500' : 'bg-amber-500',
            },
        ];

        return (
            <Card className="flex h-[300px] w-full flex-col">
                <CardHeader className="pb-2">
                    <CardTitle>Cash Flow Summary</CardTitle>
                </CardHeader>
                <CardContent className="flex-1 overflow-hidden pb-4">
                    <div className="flex h-full w-full flex-col justify-center space-y-4 pr-4">
                        {bars.map((bar) => {
                            const widthStr = `${Math.max((Math.abs(bar.value) / maxValue) * 100, 2)}%`;
                            return (
                                <div
                                    key={bar.label}
                                    className="flex flex-col space-y-1"
                                >
                                    <div className="flex items-center justify-between text-sm">
                                        <span className="truncate pr-4 font-medium text-muted-foreground">
                                            {bar.label}
                                        </span>
                                        <span className="font-bold tabular-nums">
                                            {formatCurrency(bar.value)}
                                        </span>
                                    </div>
                                    <div className="h-6 w-full overflow-hidden rounded bg-secondary">
                                        <div
                                            className={`h-full rounded-r ${bar.color} transition-all duration-500`}
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
    },
);
