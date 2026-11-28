import type { AuditLogItem } from '@/components/audit-logs/Columns';
import { ViewField } from '@/components/common/ViewField';
import { ViewModalShell } from '@/components/common/ViewModalShell';
import { Button } from '@/components/ui/button';
import { memo } from 'react';

interface AuditLogDetailModalProps {
    item: AuditLogItem | null;
    onClose: () => void;
}

function formatValue(value: unknown): string {
    if (value === null || value === undefined) {
        return '-';
    }
    if (typeof value === 'object') {
        return JSON.stringify(value);
    }
    return String(value);
}

export const AuditLogDetailModal = memo(function AuditLogDetailModal({
    item,
    onClose,
}: Readonly<AuditLogDetailModalProps>) {
    const changes = item?.attribute_changes ?? {};
    const oldValues = (changes.old ?? {}) as Record<string, unknown>;
    const newValues = (changes.attributes ?? {}) as Record<string, unknown>;
    const keys = Array.from(
        new Set([...Object.keys(oldValues), ...Object.keys(newValues)]),
    );

    return (
        <ViewModalShell
            open={item !== null}
            onClose={onClose}
            title={item?.description ?? ''}
            description={
                item
                    ? `${item.subject_type ?? '-'} #${item.subject_id ?? '-'}`
                    : ''
            }
            footer={
                <Button type="button" variant="outline" onClick={onClose}>
                    Close
                </Button>
            }
        >
            {item ? (
                <div className="space-y-3">
                    <ViewField label="User" value={item.causer_name ?? '-'} />
                    {keys.length === 0 ? (
                        <p className="text-sm text-muted-foreground">-</p>
                    ) : (
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left">
                                    <th className="py-1 pr-2">Field</th>
                                    <th className="py-1 pr-2">Old</th>
                                    <th className="py-1">New</th>
                                </tr>
                            </thead>
                            <tbody>
                                {keys.map((key) => (
                                    <tr key={key} className="align-top">
                                        <td className="py-1 pr-2 font-medium">
                                            {key}
                                        </td>
                                        <td className="py-1 pr-2 break-all">
                                            {formatValue(oldValues[key])}
                                        </td>
                                        <td className="py-1 break-all">
                                            {formatValue(newValues[key])}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            ) : null}
        </ViewModalShell>
    );
});
