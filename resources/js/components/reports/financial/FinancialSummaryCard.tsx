import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

type FinancialStatusBadgeProps = {
    isPositive: boolean;
    positiveLabel: ReactNode;
    negativeLabel: ReactNode;
    className?: string;
};

type FinancialSummaryCardProps = {
    description: ReactNode;
    isPositive: boolean;
    status: ReactNode;
    children: ReactNode;
    className?: string;
    contentClassName?: string;
};

export function FinancialStatusBadge({
    isPositive,
    positiveLabel,
    negativeLabel,
    className,
}: Readonly<FinancialStatusBadgeProps>) {
    return (
        <Badge
            variant={isPositive ? 'secondary' : 'destructive'}
            className={cn(
                isPositive &&
                    'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
                className,
            )}
        >
            {isPositive ? positiveLabel : negativeLabel}
        </Badge>
    );
}

export function FinancialSummaryCard({
    description,
    isPositive,
    status,
    children,
    className,
    contentClassName,
}: Readonly<FinancialSummaryCardProps>) {
    return (
        <Card
            className={cn(
                'overflow-hidden border-t-4',
                isPositive ? 'border-emerald-500' : 'border-destructive',
                className,
            )}
        >
            <CardHeader className="bg-muted/15">
                <div className="flex items-start justify-between gap-3">
                    <div className="space-y-1">
                        <CardTitle className="text-base">Summary</CardTitle>
                        <CardDescription className="text-xs">
                            {description}
                        </CardDescription>
                    </div>
                    {status}
                </div>
            </CardHeader>
            <CardContent className={cn('grid gap-4', contentClassName)}>
                {children}
            </CardContent>
        </Card>
    );
}
