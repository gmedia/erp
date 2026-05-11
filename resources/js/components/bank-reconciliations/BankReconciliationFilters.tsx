import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';
import { AsyncSelect } from '@/components/common/AsyncSelect';

const statusOptions = [
    { value: 'in_progress', label: 'In Progress' },
    { value: 'completed', label: 'Completed' },
];

export function createBankReconciliationFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search...'),
        createSelectFilterField('status', 'Status', statusOptions, 'Select Status'),
        {
            name: 'account_id',
            label: 'Account',
            component: <AsyncSelect url="/api/accounts?is_active=1&has_children=0" placeholder="Select Account" />,
        },
    ];
}
