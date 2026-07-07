import { KpiCard } from '@/components/common/KpiCard';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { formatCurrency } from '@/lib/utils';
import { TrendingDown, TrendingUp } from 'lucide-react';
import { memo } from 'react';
import type { AgingSummary } from '../../hooks/useAgingDashboard';

interface AgingSummaryCardsProps {
    readonly arSummary?: AgingSummary;
    readonly apSummary?: AgingSummary;
    readonly isLoading?: boolean;
}

export const AgingSummaryCards = memo<AgingSummaryCardsProps>(
    function AgingSummaryCards({ arSummary, apSummary, isLoading }) {
        if (isLoading || !arSummary || !apSummary) {
            return (
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {['card-1', 'card-2', 'card-3', 'card-4'].map((key) => (
                        <Skeleton key={key} className="h-32 w-full" />
                    ))}
                </div>
            );
        }

        const getOverdueBadgeVariant = (
            percentage: number,
        ): 'default' | 'secondary' | 'destructive' => {
            if (percentage === 0) return 'default';
            if (percentage < 10) return 'secondary';
            if (percentage < 25) return 'default';
            return 'destructive';
        };

        const cards = [
            {
                title: 'Total Receivables',
                icon: TrendingUp,
                borderColor: 'border-l-emerald-500',
                iconColor: 'text-emerald-500',
                value: arSummary.total_outstanding,
                formattedValue: formatCurrency(arSummary.total_outstanding),
                footer: (
                    <div className="mt-2 flex items-center gap-2">
                        <span className="text-xs text-muted-foreground">
                            {arSummary.invoice_count} open invoices
                        </span>
                    </div>
                ),
            },
            {
                title: 'AR Overdue',
                icon: TrendingDown,
                borderColor: 'border-l-amber-500',
                iconColor: 'text-amber-500',
                value: arSummary.overdue_amount,
                formattedValue: formatCurrency(arSummary.overdue_amount),
                footer: (
                    <div className="mt-2 flex items-center gap-2">
                        <Badge
                            variant={getOverdueBadgeVariant(
                                arSummary.overdue_percentage,
                            )}
                            className="text-xs font-medium"
                        >
                            {arSummary.overdue_percentage.toFixed(1)}%
                        </Badge>
                        <span className="text-xs text-muted-foreground">
                            {arSummary.overdue_count} overdue
                        </span>
                    </div>
                ),
            },
            {
                title: 'Total Payables',
                icon: TrendingDown,
                borderColor: 'border-l-rose-500',
                iconColor: 'text-rose-500',
                value: apSummary.total_outstanding,
                formattedValue: formatCurrency(apSummary.total_outstanding),
                footer: (
                    <div className="mt-2 flex items-center gap-2">
                        <span className="text-xs text-muted-foreground">
                            {apSummary.invoice_count} open bills
                        </span>
                    </div>
                ),
            },
            {
                title: 'AP Overdue',
                icon: TrendingUp,
                borderColor: 'border-l-orange-500',
                iconColor: 'text-orange-500',
                value: apSummary.overdue_amount,
                formattedValue: formatCurrency(apSummary.overdue_amount),
                footer: (
                    <div className="mt-2 flex items-center gap-2">
                        <Badge
                            variant={getOverdueBadgeVariant(
                                apSummary.overdue_percentage,
                            )}
                            className="text-xs font-medium"
                        >
                            {apSummary.overdue_percentage.toFixed(1)}%
                        </Badge>
                        <span className="text-xs text-muted-foreground">
                            {apSummary.overdue_count} overdue
                        </span>
                    </div>
                ),
            },
        ];

        return (
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
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
    },
);
