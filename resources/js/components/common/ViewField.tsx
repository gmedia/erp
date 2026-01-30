'use client';

import * as React from 'react';
import { Label } from '@/components/ui/label';

interface ViewFieldProps {
    label: string;
    value: React.ReactNode;
}

/**
 * ViewField - A consistent field display component for view modals.
 * Displays a label and value with standard styling.
 */
export function ViewField({ label, value }: ViewFieldProps) {
    return (
        <div className="grid gap-1">
            <Label className="text-muted-foreground">
                {label}
            </Label>
            <p className="text-sm font-medium">{value || '-'}</p>
        </div>
    );
}
