import { KpiCard } from '@/components/common/KpiCard';
import { Badge } from '@/components/ui/badge';
import { formatCurrency } from '@/lib/utils';
import {
    Banknote,
    Building2,
    CreditCard,
    DollarSign,
    PiggyBank,
    Receipt,
    Wallet,
} from 'lucide-react';
import { memo } from 'react';
import type {
    FinancialDashboardData,
    KpiItem,
} from '../../hooks/useFinancialDashboard';

interface SummaryCardsProps {
    readonly data?: FinancialDashboardData['kpis'];
    readonly isLoading: boolean;
    readonly showComparison?: boolean;
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

function comparisonFooter(
    change: number,
    isExpenseOrLiability: boolean,
    scope: KpiItem['scope'] | undefined,
    showComparison: boolean,
) {
    return (
        <>
            {showComparison && (
                <div className="mt-2 flex items-center gap-2">
                    {getChangeBadge(change, isExpenseOrLiability)}
                    <span className="text-xs text-muted-foreground">
                        vs comparison period
                    </span>
                </div>
            )}
            <div className={showComparison ? 'mt-1.5' : 'mt-2'}>
                {getScopePill(scope)}
            </div>
        </>
    );
}

function signedBalanceChrome(
    value: number,
    positive: { iconColor: string },
): {
    iconColor: string;
    valueClassName?: string;
} {
    if (value < 0) {
        return {
            iconColor: 'text-destructive-foreground',
            valueClassName: 'text-destructive-foreground',
        };
    }

    return positive;
}

export const SummaryCards = memo<SummaryCardsProps>(function SummaryCards({
    data,
    isLoading,
    showComparison = false,
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
                    <div
                        key={key}
                        className="animate-pulse rounded-lg border bg-card p-6 shadow-sm"
                    >
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
            // Ikon deskriptif: panah tren tanpa delta menyesatkan (W5-12).
            icon: Wallet,
            iconColor: 'text-success',
            value: data.revenue.value,
            formattedValue: formatCurrency(data.revenue.value),
            footer: comparisonFooter(
                data.revenue.change,
                false,
                data.revenue.scope,
                showComparison,
            ),
        },
        {
            title: 'Expenses',
            icon: Receipt,
            iconColor: 'text-destructive-foreground',
            value: data.expenses.value,
            formattedValue: formatCurrency(data.expenses.value),
            footer: comparisonFooter(
                data.expenses.change,
                true,
                data.expenses.scope,
                showComparison,
            ),
        },
        {
            title: 'Net Income',
            icon: DollarSign,
            ...signedBalanceChrome(data.net_income.value, {
                iconColor: 'text-info',
            }),
            value: data.net_income.value,
            formattedValue: formatCurrency(data.net_income.value),
            footer: comparisonFooter(
                data.net_income.change,
                false,
                data.net_income.scope,
                showComparison,
            ),
        },
        {
            title: 'Total Assets',
            icon: Building2,
            iconColor: 'text-chart-3',
            value: data.total_assets.value,
            formattedValue: formatCurrency(data.total_assets.value),
            footer: comparisonFooter(
                data.total_assets.change,
                false,
                data.total_assets.scope,
                showComparison,
            ),
        },
        {
            title: 'Total Liabilities',
            icon: CreditCard,
            iconColor: 'text-warning',
            value: data.total_liabilities.value,
            formattedValue: formatCurrency(data.total_liabilities.value),
            footer: comparisonFooter(
                data.total_liabilities.change,
                true,
                data.total_liabilities.scope,
                showComparison,
            ),
        },
        {
            title: 'Equity',
            icon: PiggyBank,
            ...signedBalanceChrome(data.equity.value, {
                iconColor: 'text-chart-3',
            }),
            value: data.equity.value,
            formattedValue: formatCurrency(data.equity.value),
            footer: comparisonFooter(
                data.equity.change,
                false,
                data.equity.scope,
                showComparison,
            ),
        },
        {
            title: 'Cash Balance',
            icon: Banknote,
            ...signedBalanceChrome(data.cash_balance.value, {
                iconColor: 'text-success',
            }),
            value: data.cash_balance.value,
            formattedValue: formatCurrency(data.cash_balance.value),
            footer: comparisonFooter(
                data.cash_balance.change,
                false,
                data.cash_balance.scope,
                showComparison,
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
                    formattedValue={card.formattedValue}
                    iconColor={card.iconColor}
                    valueClassName={card.valueClassName}
                >
                    {card.footer}
                </KpiCard>
            ))}
        </div>
    );
});
