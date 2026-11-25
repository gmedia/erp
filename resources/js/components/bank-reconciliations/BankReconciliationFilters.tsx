import { AsyncSelect } from '@/components/common/AsyncSelect';
import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

const statusOptions = [
    { value: 'in_progress', label: 'In Progress' },
    { value: 'completed', label: 'Completed' },
];

export function createBankReconciliationFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            'Search...',
        ),
        createSelectFilterField(
            'status',
            { key: 'column.status', fallback: 'Status' },
            statusOptions,
            'Select Status',
        ),
        {
            name: 'account_id',
            label: { key: 'column.account', fallback: 'Account' },
            component: (
                <AsyncSelect
                    url="/api/accounts?is_active=1&has_children=0"
                    placeholder="Select Account"
                />
            ),
        },
    ];
}
