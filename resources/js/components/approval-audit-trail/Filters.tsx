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
            'Search',
            'Search IP, Document ID, user...',
        ),
        createTextFilterField(
            'approvable_type',
            'Document Type',
            'e.g. PurchaseRequest',
        ),
        createApprovalAuditEventFilterField(),
        createUserFilterField('actor_user_id', 'Actor'),
        ...createDateRangeFilterFields(),
    ];
}
