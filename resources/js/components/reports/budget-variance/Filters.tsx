import { AsyncSelect } from '@/components/common/AsyncSelect';
import {
    createSelectFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

const varianceStatusOptions = [
    { value: 'within_budget', label: 'Within Budget' },
    { value: 'warning', label: 'Warning' },
    { value: 'over_budget', label: 'Over Budget' },
];

const accountTypeOptions = [
    { value: 'asset', label: 'Asset' },
    { value: 'liability', label: 'Liability' },
    { value: 'equity', label: 'Equity' },
    { value: 'revenue', label: 'Revenue' },
    { value: 'expense', label: 'Expense' },
];

export function createBudgetVarianceFilterFields(): FieldDescriptor[] {
    return [
        {
            name: 'budget_id',
            label: 'Budget',
            component: (
                <AsyncSelect
                    url="/api/budgets"
                    placeholder="Select budget"
                />
            ),
        },
        createSelectFilterField('status', 'Status', varianceStatusOptions, 'All statuses'),
        createSelectFilterField('account_type', 'Account Type', accountTypeOptions, 'All types'),
    ];
}
