import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { formatCurrency } from '@/lib/utils';
import {
    TrendingUp,
    TrendingDown,
    DollarSign,
    Building2,
    CreditCard,
    PiggyBank,
    Banknote,
} from 'lucide-react';
import { memo } from 'react';
import type { FinancialDashboardData } from '../../hooks/useFinancialDashboard';

interface SummaryCardsProps {
    readonly data?: FinancialDashboardData['kpis'];
    readonly isLoading: boolean;
}

function getChangeBadge(change: number, isExpenseOrLiability: boolean = false) {
    const isPositive = change > 0;
    const isNegative = change < 0;
    const isNeutral = change === 0;

    let variant: 'default' | 'destructive' | 'secondary' = 'secondary';
    if (isExpenseOrLiability) {
        if (isPositive) variant = 'destructive';
        if (isNegative) variant = 'default';
    } else {
        if (isPositive) variant = 'default';
        if (isNegative) variant = 'destructive';
    }

    if (isNeutral) variant = 'secondary';

    const sign = isPositive ? '+' : '';
    return (
        <Badge variant={variant} className="text-xs font-medium">
            {sign}
            {change.toFixed(2)}%
        </Badge>
    );
}

export const SummaryCards = memo<SummaryCardsProps>(function SummaryCards({
    data,
    isLoading,
}) {
    if (isLoading || !data) {
        return (
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {[
                    'summary-1',
                    'summary-2',
                    'summary-3',
                    'summary-4',
                    'summary-5',
                    'summary-6',
                    'summary-7',
                ].map((key) => (
                    <Card key={key} className="animate-pulse">
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="h-4 w-24 rounded bg-muted text-sm font-medium text-transparent"></CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="mt-1 h-8 w-24 rounded bg-muted text-2xl font-bold text-transparent"></div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        );
    }

    const cards = [
        {
            title: 'Revenue',
            icon: TrendingUp,
            borderColor: 'border-l-emerald-500',
            iconColor: 'text-emerald-500',
            value: data.revenue.value,
            change: data.revenue.change,
            isExpenseOrLiability: false,
        },
        {
            title: 'Expenses',
            icon: TrendingDown,
            borderColor: 'border-l-rose-500',
            iconColor: 'text-rose-500',
            value: data.expenses.value,
            change: data.expenses.change,
            isExpenseOrLiability: true,
        },
        {
            title: 'Net Income',
            icon: DollarSign,
            borderColor: 'border-l-blue-500',
            iconColor: 'text-blue-500',
            value: data.net_income.value,
            change: data.net_income.change,
            isExpenseOrLiability: false,
        },
        {
            title: 'Total Assets',
            icon: Building2,
            borderColor: 'border-l-indigo-500',
            iconColor: 'text-indigo-500',
            value: data.total_assets.value,
            change: data.total_assets.change,
            isExpenseOrLiability: false,
        },
        {
            title: 'Total Liabilities',
            icon: CreditCard,
            borderColor: 'border-l-amber-500',
            iconColor: 'text-amber-500',
            value: data.total_liabilities.value,
            change: data.total_liabilities.change,
            isExpenseOrLiability: true,
        },
        {
            title: 'Equity',
            icon: PiggyBank,
            borderColor: 'border-l-purple-500',
            iconColor: 'text-purple-500',
            value: data.equity.value,
            change: data.equity.change,
            isExpenseOrLiability: false,
        },
        {
            title: 'Cash Balance',
            icon: Banknote,
            borderColor: 'border-l-teal-500',
            iconColor: 'text-teal-500',
            value: data.cash_balance.value,
            change: data.cash_balance.change,
            isExpenseOrLiability: false,
        },
    ];

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {cards.map((card) => {
                const Icon = card.icon;

                return (
                    <Card
                        key={card.title}
                        className={`overflow-hidden border-l-4 ${card.borderColor} transition-all hover:shadow-md`}
                    >
                        <CardHeader className="flex flex-row items-center justify-between pt-4 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                {card.title}
                            </CardTitle>
                            <Icon className={`h-4 w-4 ${card.iconColor}`} />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {formatCurrency(card.value)}
                            </div>
                            <div className="mt-2 flex items-center gap-2">
                                {getChangeBadge(
                                    card.change,
                                    card.isExpenseOrLiability,
                                )}
                                <span className="text-xs text-muted-foreground">
                                    vs comparison period
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
});
