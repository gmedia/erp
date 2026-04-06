import type { ReactNode } from 'react';

type AuditTrailFieldProps = {
    label: string;
    value: ReactNode;
    helperText?: ReactNode;
    className?: string;
    valueClassName?: string;
};

export function AuditTrailField({
    label,
    value,
    helperText,
    className,
    valueClassName = 'text-sm font-medium',
}: Readonly<AuditTrailFieldProps>) {
    return (
        <div className={className ?? 'space-y-1'}>
            <p className="text-sm font-medium text-muted-foreground">{label}</p>
            <div className={valueClassName}>{value}</div>
            {helperText ? (
                <div className="text-xs text-muted-foreground">{helperText}</div>
            ) : null}
        </div>
    );
}

type AuditTrailMetadataSnapshotProps = {
    metadata?: Record<string, unknown> | null;
};

export function AuditTrailMetadataSnapshot({
    metadata,
}: Readonly<AuditTrailMetadataSnapshotProps>) {
    if (!metadata || Object.keys(metadata).length === 0) {
        return null;
    }

    return (
        <div className="space-y-2">
            <p className="text-sm font-medium text-muted-foreground">
                Metadata Snapshot
            </p>
            <pre className="overflow-x-auto rounded-lg bg-muted p-4 text-xs">
                <code>{JSON.stringify(metadata, null, 2)}</code>
            </pre>
        </div>
    );
}