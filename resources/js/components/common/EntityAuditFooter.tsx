import { formatDate } from '@/utils/formatters';

type AuditStampPerson = { name?: string | null } | null;

interface EntityAuditFooterProps {
    createdBy?: AuditStampPerson;
    confirmedBy?: AuditStampPerson;
    confirmedAt?: string | Date | null;
}

export function EntityAuditFooter({
    createdBy,
    confirmedBy,
    confirmedAt,
}: Readonly<EntityAuditFooterProps>) {
    return (
        <div className="pt-4 text-sm text-muted-foreground">
            <div>Created by: {createdBy?.name || 'System'}</div>
            {confirmedAt && (
                <div>
                    Confirmed by: {confirmedBy?.name || 'System'} on{' '}
                    {formatDate(confirmedAt)}
                </div>
            )}
        </div>
    );
}
