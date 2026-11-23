import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/utils';
import { CircleDollarSign } from 'lucide-react';
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
                            {[60, 80, 40].map((width) => (
                                <div
                                    key={`cash-flow-placeholder-${width}`}
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
                        <CircleDollarSign className="h-8 w-8 opacity-50" />
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
                color: 'bg-success',
            },
            {
                label: 'Cash Outflow',
                value: data.outflow,
                color: 'bg-destructive-foreground',
            },
            {
                label: 'Net Cash Flow',
                value: data.net,
                color: data.net >= 0 ? 'bg-info' : 'bg-destructive-foreground',
            },
        ];

        return (
            <Card className="flex h-[300px] w-full flex-col">
                <CardHeader className="pb-2">
                    <CardTitle className="text-base font-semibold">
                        Cash Flow Summary
                    </CardTitle>
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
