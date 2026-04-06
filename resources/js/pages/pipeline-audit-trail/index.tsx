'use client';

import { AuditTrailPage } from '@/components/common/AuditTrailPage';
import {
    createPipelineAuditTrailColumns,
    PipelineAuditTrailItem,
} from '@/components/pipeline-audit-trail/Columns';
import { DetailModal } from '@/components/pipeline-audit-trail/DetailModal';
import { createPipelineAuditTrailFilterFields } from '@/components/pipeline-audit-trail/Filters';

export default function PipelineAuditTrail() {
    return (
        <AuditTrailPage<PipelineAuditTrailItem>
            title="Pipeline Audit Trail"
            breadcrumbs={[
                { title: 'Admin', href: '#' },
                {
                    title: 'Pipeline Audit Trail',
                    href: '/pipeline-audit-trail',
                },
            ]}
            filterFields={createPipelineAuditTrailFilterFields()}
            initialFilters={{
                search: '',
                entity_type: '',
                pipeline_id: '',
                performed_by: '',
                start_date: '',
                end_date: '',
            }}
            endpoint="/api/pipeline-audit-trail"
            queryKey={['pipeline-audit-trail']}
            entityName="Audit Trail"
            exportEndpoint="/api/pipeline-audit-trail/export"
            buildColumns={createPipelineAuditTrailColumns}
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
