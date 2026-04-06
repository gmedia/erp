import {
    createDateRangeFilterFields,
    createSelectFilterField,
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
        createSelectFilterField(
            'event',
            'Event',
            [
                { value: 'submitted', label: 'Submitted' },
                { value: 'step_approved', label: 'Step Approved' },
                { value: 'step_rejected', label: 'Step Rejected' },
                { value: 'step_skipped', label: 'Step Skipped' },
                { value: 'auto_approved', label: 'Auto Approved' },
                { value: 'escalated', label: 'Escalated' },
                { value: 'delegated', label: 'Delegated' },
                { value: 'cancelled', label: 'Cancelled' },
                { value: 'resubmitted', label: 'Resubmitted' },
                { value: 'completed', label: 'Completed' },
            ],
            'Select Event',
        ),
        createUserFilterField('actor_user_id', 'Actor'),
        ...createDateRangeFilterFields(),
    ];
}
