import { formatDateByRegionalSettings } from '@/utils/date-format';

type AuditStampPerson = { name?: string | null } | null | undefined;

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
                    {formatDateByRegionalSettings(confirmedAt, {
                        locale: 'id-ID',
                    })}
                </div>
            )}
        </div>
    );
}
