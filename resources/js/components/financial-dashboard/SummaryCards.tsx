import { Badge } from '@/components/ui/badge';
import { KpiCard } from '@/components/common/KpiCard';
import { formatCurrency } from '@/lib/utils';
import {
    Banknote,
    Building2,
    CreditCard,
    DollarSign,
    PiggyBank,
    TrendingDown,
    TrendingUp,
} from 'lucide-react';
import { memo } from 'react';
import type {
    FinancialDashboardData,
    KpiItem,
} from '../../hooks/useFinancialDashboard';

interface SummaryCardsProps {
    readonly data?: FinancialDashboardData['kpis'];
    readonly isLoading: boolean;
}

function getScopePill(scope?: KpiItem['scope']) {
    if (scope === 'branch') {
        return (
            <Badge
                variant="outline"
                className="px-1.5 py-0 text-[10px] font-normal text-muted-foreground"
            >
                Segment
            </Badge>
        );
    }
    return (
        <Badge
            variant="outline"
            className="px-1.5 py-0 text-[10px] font-normal text-muted-foreground"
        >
            Company-wide
        </Badge>
    );
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
                    <div key={key} className="animate-pulse rounded-lg border bg-card p-6 shadow-sm">
                        <div className="flex items-center justify-between pb-2">
                            <div className="h-4 w-24 rounded bg-muted"></div>
                        </div>
                        <div className="mt-1 h-8 w-24 rounded bg-muted"></div>
                    </div>
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
            formattedValue: formatCurrency(data.revenue.value),
            footer: (
                <>
                    <div className="mt-2 flex items-center gap-2">
                        {getChangeBadge(
                            data.revenue.change,
                            false,
                        )}
                        <span className="text-xs text-muted-foreground">
                            vs comparison period
                        </span>
                    </div>
                    <div className="mt-1.5">
                        {getScopePill(data.revenue.scope)}
                    </div>
                </>
            ),
        },
        {
            title: 'Expenses',
            icon: TrendingDown,
            borderColor: 'border-l-rose-500',
            iconColor: 'text-rose-500',
            value: data.expenses.value,
            formattedValue: formatCurrency(data.expenses.value),
            footer: (
                <>
                    <div className="mt-2 flex items-center gap-2">
                        {getChangeBadge(
                            data.expenses.change,
                            true,
                        )}
                        <span className="text-xs text-muted-foreground">
                            vs comparison period
                        </span>
                    </div>
                    <div className="mt-1.5">
                        {getScopePill(data.expenses.scope)}
                    </div>
                </>
            ),
        },
        {
            title: 'Net Income',
            icon: DollarSign,
            borderColor: 'border-l-blue-500',
            iconColor: 'text-blue-500',
            value: data.net_income.value,
            formattedValue: formatCurrency(data.net_income.value),
            footer: (
                <>
                    <div className="mt-2 flex items-center gap-2">
                        {getChangeBadge(
                            data.net_income.change,
                            false,
                        )}
                        <span className="text-xs text-muted-foreground">
                            vs comparison period
                        </span>
                    </div>
                    <div className="mt-1.5">
                        {getScopePill(data.net_income.scope)}
                    </div>
                </>
            ),
        },
        {
            title: 'Total Assets',
            icon: Building2,
            borderColor: 'border-l-indigo-500',
            iconColor: 'text-indigo-500',
            value: data.total_assets.value,
            formattedValue: formatCurrency(data.total_assets.value),
            footer: (
                <>
                    <div className="mt-2 flex items-center gap-2">
                        {getChangeBadge(
                            data.total_assets.change,
                            false,
                        )}
                        <span className="text-xs text-muted-foreground">
                            vs comparison period
                        </span>
                    </div>
                    <div className="mt-1.5">
                        {getScopePill(data.total_assets.scope)}
                    </div>
                </>
            ),
        },
        {
            title: 'Total Liabilities',
            icon: CreditCard,
            borderColor: 'border-l-amber-500',
            iconColor: 'text-amber-500',
            value: data.total_liabilities.value,
            formattedValue: formatCurrency(data.total_liabilities.value),
            footer: (
                <>
                    <div className="mt-2 flex items-center gap-2">
                        {getChangeBadge(
                            data.total_liabilities.change,
                            true,
                        )}
                        <span className="text-xs text-muted-foreground">
                            vs comparison period
                        </span>
                    </div>
                    <div className="mt-1.5">
                        {getScopePill(data.total_liabilities.scope)}
                    </div>
                </>
            ),
        },
        {
            title: 'Equity',
            icon: PiggyBank,
            borderColor: 'border-l-purple-500',
            iconColor: 'text-purple-500',
            value: data.equity.value,
            formattedValue: formatCurrency(data.equity.value),
            footer: (
                <>
                    <div className="mt-2 flex items-center gap-2">
                        {getChangeBadge(
                            data.equity.change,
                            false,
                        )}
                        <span className="text-xs text-muted-foreground">
                            vs comparison period
                        </span>
                    </div>
                    <div className="mt-1.5">
                        {getScopePill(data.equity.scope)}
                    </div>
                </>
            ),
        },
        {
            title: 'Cash Balance',
            icon: Banknote,
            borderColor: 'border-l-teal-500',
            iconColor: 'text-teal-500',
            value: data.cash_balance.value,
            formattedValue: formatCurrency(data.cash_balance.value),
            footer: (
                <>
                    <div className="mt-2 flex items-center gap-2">
                        {getChangeBadge(
                            data.cash_balance.change,
                            false,
                        )}
                        <span className="text-xs text-muted-foreground">
                            vs comparison period
                        </span>
                    </div>
                    <div className="mt-1.5">
                        {getScopePill(data.cash_balance.scope)}
                    </div>
                </>
            ),
        },
    ];

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {cards.map((card) => (
                <KpiCard
                    key={card.title}
                    title={card.title}
                    icon={card.icon}
                    value={card.value}
                    formattedValue={card.formattedValue}
                    borderColor={card.borderColor}
                    iconColor={card.iconColor}
                >
                    {card.footer}
                </KpiCard>
            ))}
        </div>
    );
});
