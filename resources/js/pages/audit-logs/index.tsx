'use client';

import { AuditLogDetailModal } from '@/components/audit-logs/AuditLogDetailModal';
import {
    createAuditLogColumns,
    type AuditLogItem,
} from '@/components/audit-logs/Columns';
import { createAuditLogFilterFields } from '@/components/audit-logs/Filters';
import { ReportDataTablePage } from '@/components/common/ReportDataTablePage';
import { useTranslation } from '@/contexts/i18n-context';
import { useState } from 'react';

export default function AuditLogs() {
    const { t } = useTranslation();
    const [detailItem, setDetailItem] = useState<AuditLogItem | null>(null);

    return (
        <>
            <ReportDataTablePage<AuditLogItem>
                title="Audit Log"
                titleKey="menu.audit_log"
                descriptionKey="page.audit_logs.description"
                breadcrumbs={[
                    { title: 'Admin', href: '#' },
                    {
                        title: 'Audit Log',
                        titleKey: 'menu.audit_log',
                        href: '/audit-logs',
                    },
                ]}
                columns={createAuditLogColumns(setDetailItem)}
                filterFields={createAuditLogFilterFields(t)}
                initialFilters={{
                    search: '',
                    subject_type: '',
                    causer_id: '',
                    start_date: '',
                    end_date: '',
                }}
                endpoint="/api/audit-logs"
                queryKey={['audit-logs']}
                entityName="Audit Log"
                exportEndpoint="/api/audit-logs/export"
            />
            <AuditLogDetailModal
                item={detailItem}
                onClose={() => setDetailItem(null)}
            />
        </>
    );
}
