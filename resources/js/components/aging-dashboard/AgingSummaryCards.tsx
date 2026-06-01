import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
                subtitle: `${arSummary.invoice_count} open invoices`,
            },
            {
                title: 'AR Overdue',
                icon: TrendingDown,
                borderColor: 'border-l-amber-500',
                iconColor: 'text-amber-500',
                value: arSummary.overdue_amount,
                badge: `${arSummary.overdue_percentage.toFixed(1)}%`,
                badgeVariant: getOverdueBadgeVariant(
                    arSummary.overdue_percentage,
                ),
                subtitle: `${arSummary.overdue_count} overdue`,
            },
            {
                title: 'Total Payables',
                icon: TrendingDown,
                borderColor: 'border-l-rose-500',
                iconColor: 'text-rose-500',
                value: apSummary.total_outstanding,
                subtitle: `${apSummary.invoice_count} open bills`,
            },
            {
                title: 'AP Overdue',
                icon: TrendingUp,
                borderColor: 'border-l-orange-500',
                iconColor: 'text-orange-500',
                value: apSummary.overdue_amount,
                badge: `${apSummary.overdue_percentage.toFixed(1)}%`,
                badgeVariant: getOverdueBadgeVariant(
                    apSummary.overdue_percentage,
                ),
                subtitle: `${apSummary.overdue_count} overdue`,
            },
        ];

        return (
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
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
                                    {card.badge && (
                                        <Badge
                                            variant={card.badgeVariant}
                                            className="text-xs font-medium"
                                        >
                                            {card.badge}
                                        </Badge>
                                    )}
                                    <span className="text-xs text-muted-foreground">
                                        {card.subtitle}
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>
        );
    },
);
