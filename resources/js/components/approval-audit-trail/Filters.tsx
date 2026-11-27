import {
    createApprovalAuditEventFilterField,
    createDateRangeFilterFields,
    createTextFilterField,
    createUserFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createApprovalAuditTrailFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'filter_field.search', fallback: 'Search' },
            {
                key: 'page.approval_audit_trail.search_placeholder',
                fallback: 'Search IP, Document ID, user...',
            },
        ),
        createTextFilterField(
            'approvable_type',
            { key: 'filter_field.document_type', fallback: 'Document Type' },
            'e.g. PurchaseRequest',
        ),
        createApprovalAuditEventFilterField(),
        createUserFilterField('actor_user_id', {
            key: 'filter_field.actor',
            fallback: 'Actor',
        }),
        ...createDateRangeFilterFields(),
    ];
}
