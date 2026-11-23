import { KpiCard } from '@/components/common/KpiCard';
import { Badge } from '@/components/ui/badge';
import { cn, formatCurrency } from '@/lib/utils';
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

const MUTED_ICON = 'text-muted-foreground';

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

function signedBalanceChrome(value: number): { valueClassName?: string } {
    return value < 0 ? { valueClassName: 'text-destructive-foreground' } : {};
}

export const SummaryCards = memo<SummaryCardsProps>(function SummaryCards({
    data,
    isLoading,
    showComparison = false,
}) {
    if (isLoading || !data) {
        return (
            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
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
            iconColor: MUTED_ICON,
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
            iconColor: MUTED_ICON,
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
            iconColor: MUTED_ICON,
            ...signedBalanceChrome(data.net_income.value),
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
            iconColor: MUTED_ICON,
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
            iconColor: MUTED_ICON,
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
            iconColor: MUTED_ICON,
            ...signedBalanceChrome(data.equity.value),
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
            iconColor: MUTED_ICON,
            ...signedBalanceChrome(data.cash_balance.value),
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
        <div className="grid grid-cols-2 gap-4 lg:grid-cols-12">
            {cards.map((card, index) => (
                <div
                    key={card.title}
                    className={cn(
                        index === cards.length - 1 && 'col-span-2',
                        index < 4 ? 'lg:col-span-3' : 'lg:col-span-4',
                    )}
                >
                    <KpiCard
                        title={card.title}
                        icon={card.icon}
                        formattedValue={card.formattedValue}
                        iconColor={card.iconColor}
                        valueClassName={cn(
                            'text-lg sm:text-xl',
                            card.valueClassName,
                        )}
                    >
                        {card.footer}
                    </KpiCard>
                </div>
            ))}
        </div>
    );
});
