'use client';

import { Badge } from '@/components/ui/badge';
import type { ComponentProps, ReactNode } from 'react';

type DisplayValue = ReactNode | null | undefined;

type WarehouseSummary = {
    name: string | null;
    code?: string | null;
    branch?: { name: string | null } | null;
} | null | undefined;

function resolveDisplayValue(value: DisplayValue): ReactNode {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return value;
}

export function formatReportLabel(value: string | null | undefined): string {
    if (!value) return '-';
    return value.replaceAll('_', ' ');
}

export function formatWarehouseCodeLabel(
    code: string | null | undefined,
    branchName: string | null | undefined,
): string {
    return (code ?? '-') + (branchName ? ` • ${branchName}` : '');
}

export function SummaryCell({
    primary,
    secondary,
}: {
    primary: DisplayValue;
    secondary: DisplayValue;
}) {
    return (
        <div className="space-y-0.5">
            <div className="font-medium">{resolveDisplayValue(primary)}</div>
            <div className="text-xs text-muted-foreground">
                {resolveDisplayValue(secondary)}
            </div>
        </div>
    );
}

export function TextCell({
    value,
    className,
}: {
    value: DisplayValue;
    className?: string;
}) {
    return <div className={className}>{resolveDisplayValue(value)}</div>;
}

export function WarehouseSummaryCell({
    warehouse,
}: {
    warehouse: WarehouseSummary;
}) {
    return (
        <SummaryCell
            primary={warehouse?.name}
            secondary={formatWarehouseCodeLabel(
                warehouse?.code,
                warehouse?.branch?.name,
            )}
        />
    );
}

export function StatusBadgeCell({
    value,
    variant = 'outline',
}: {
    value: string | null | undefined;
    variant?: ComponentProps<typeof Badge>['variant'];
}) {
    return (
        <Badge variant={variant} className="capitalize">
            {formatReportLabel(value)}
        </Badge>
    );
}