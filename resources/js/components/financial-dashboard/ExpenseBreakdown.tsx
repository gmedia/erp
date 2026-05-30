import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCurrency } from '@/lib/utils';
import { memo } from 'react';
import type { ExpenseBreakdownItem } from '../../hooks/useFinancialDashboard';

interface ExpenseBreakdownProps {
    readonly data?: ExpenseBreakdownItem[];
    readonly isLoading: boolean;
}

const EXPENSE_PLACEHOLDER_HEIGHTS = [70, 50, 85, 40, 60] as const;

export const ExpenseBreakdown = memo<ExpenseBreakdownProps>(
    function ExpenseBreakdown({ data, isLoading }) {
        if (isLoading) {
            return (
                <Card className="flex h-[300px] w-full items-center justify-center">
                    <div className="flex flex-col items-center space-y-4">
                        <div className="flex h-24 w-full flex-col space-y-3 px-8 opacity-20">
                            {EXPENSE_PLACEHOLDER_HEIGHTS.map((width, idx) => (
                                <div
                                    key={`expense-placeholder-${idx}`}
                                    className="h-4 animate-pulse rounded bg-muted-foreground"
                                    style={{ width: `${width}%` }}
                                ></div>
                            ))}
                        </div>
                        <div className="text-sm text-muted-foreground">
                            Loading expenses...
                        </div>
                    </div>
                </Card>
            );
        }

        if (!data || data.length === 0) {
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
                                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                            />
                        </svg>
                    </div>
                    No expense data available.
                </Card>
            );
        }

        const displayData = data.slice(0, 8);
        const maxValue = Math.max(...displayData.map((d) => d.value), 1);

        return (
            <Card className="flex h-[300px] w-full flex-col">
                <CardHeader className="pb-2">
                    <CardTitle>Top Expenses</CardTitle>
                </CardHeader>
                <CardContent className="flex-1 overflow-hidden pb-4">
                    <div className="flex h-full w-full flex-col justify-center space-y-3 pr-4">
                        {displayData.map((item) => {
                            const widthStr = `${Math.max((item.value / maxValue) * 100, 2)}%`;
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
                                            {formatCurrency(item.value)}
                                        </span>
                                    </div>
                                    <div className="h-3 w-full overflow-hidden rounded bg-secondary">
                                        <div
                                            className="h-full rounded-r bg-rose-500 transition-all duration-500"
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
