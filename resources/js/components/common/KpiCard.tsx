import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

export interface KpiCardProps {
    title: string;
    icon: LucideIcon;
    value: number;
    formattedValue: string;
    borderColor: string;
    iconColor: string;
    children?: ReactNode;
}

export function KpiCard({
    title,
    icon: Icon,
    formattedValue,
    borderColor,
    iconColor,
    children,
}: KpiCardProps) {
    return (
        <Card
            className={`overflow-hidden border-l-4 ${borderColor} transition-all hover:shadow-md`}
        >
            <CardHeader className="flex flex-row items-center justify-between pt-4 pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {title}
                </CardTitle>
                <Icon className={`h-4 w-4 ${iconColor}`} />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-bold">{formattedValue}</div>
                {children}
            </CardContent>
        </Card>
    );
}
