import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import { createAsyncSelectFilterField, createTextFilterField, createSelectFilterField, type FieldDescriptor } from '@/components/common/filters';

export function createApprovalAuditTrailFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search IP, Document ID, user...'),
        createTextFilterField('approvable_type', 'Document Type', 'e.g. PurchaseRequest'),
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
            'Select Event'
        ),
        createAsyncSelectFilterField(
            'actor_user_id',
            'Actor',
            '/api/users', // Assumes a generic user search endpoint
            'Select a user',
        ),
        {
            name: 'start_date',
            label: 'Start Date',
            component: <FilterDatePicker placeholder="Start Date" />,
        },
        {
            name: 'end_date',
            label: 'End Date',
            component: <FilterDatePicker placeholder="End Date" />,
        },
    ];
}
