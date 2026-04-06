'use client';

import {
    ApprovalAuditTrailItem,
    createApprovalAuditTrailColumns,
} from '@/components/approval-audit-trail/Columns';
import { DetailModal } from '@/components/approval-audit-trail/DetailModal';
import { createApprovalAuditTrailFilterFields } from '@/components/approval-audit-trail/Filters';
import { AuditTrailPage } from '@/components/common/AuditTrailPage';

export default function ApprovalAuditTrail() {
    return (
        <AuditTrailPage<ApprovalAuditTrailItem>
            title="Approval Audit Trail"
            breadcrumbs={[
                { title: 'Admin', href: '#' },
                {
                    title: 'Approval Audit Trail',
                    href: '/approval-audit-trail',
                },
            ]}
            filterFields={createApprovalAuditTrailFilterFields()}
            initialFilters={{
                search: '',
                approvable_type: '',
                event: '',
                actor_user_id: '',
                start_date: '',
                end_date: '',
            }}
            endpoint="/api/approval-audit-trail"
            queryKey={['approval-audit-trail']}
            entityName="Audit Trail"
            exportEndpoint="/api/approval-audit-trail/export"
            buildColumns={createApprovalAuditTrailColumns}
            renderDetailModal={({ item, open, onOpenChange }) => (
                <DetailModal
                    item={item}
                    open={open}
                    onOpenChange={onOpenChange}
                />
            )}
        />
    );
}
