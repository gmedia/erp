import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

export interface KpiCardProps {
    title: string;
    icon: LucideIcon;
    formattedValue: string;
    iconColor: string;
    valueClassName?: string;
    children?: ReactNode;
}

export function KpiCard({
    title,
    icon: Icon,
    formattedValue,
    iconColor,
    valueClassName,
    children,
}: Readonly<KpiCardProps>) {
    return (
        <Card className="gap-2 overflow-hidden">
            <CardHeader className="flex flex-row items-center justify-between pt-4 pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {title}
                </CardTitle>
                <Icon className={`h-4 w-4 ${iconColor}`} />
            </CardHeader>
            <CardContent className="min-w-0">
                <div
                    title={formattedValue}
                    className={`text-xl font-semibold break-keep tabular-nums min-[1400px]:text-2xl ${valueClassName ?? ''}`}
                >
                    {formattedValue}
                </div>
                {children}
            </CardContent>
        </Card>
    );
}
