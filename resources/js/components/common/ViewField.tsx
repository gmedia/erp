'use client';

import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import * as React from 'react';

interface ViewFieldProps {
    label: string;
    value: React.ReactNode;
    className?: string;
}

/**
 * ViewField - A consistent field display component for view modals.
 * Displays a label and value with standard styling.
 */
export function ViewField({
    label,
    value,
    className,
}: Readonly<ViewFieldProps>) {
    return (
        <div className="grid gap-0.5">
            <Label className="text-muted-foreground text-xs font-normal">
                {label}
            </Label>
            <div className={cn('text-sm font-medium leading-snug', className)}>
                {value || '-'}
            </div>
        </div>
    );
}
