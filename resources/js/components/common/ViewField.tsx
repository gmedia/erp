'use client';

import { Label } from '@/components/ui/label';
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
        <div className="grid gap-1">
            <Label className="text-muted-foreground">{label}</Label>
            <div className={`text-sm font-medium ${className || ''}`}>
                {value || '-'}
            </div>
        </div>
    );
}
