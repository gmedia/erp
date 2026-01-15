'use client';

import * as React from 'react';

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
        <div className="space-y-1">
            <label className="text-sm font-medium text-muted-foreground">
                {label}
            </label>
            <p className="text-sm font-medium">{value || '-'}</p>
        </div>
    );
}
