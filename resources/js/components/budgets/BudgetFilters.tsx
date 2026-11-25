import { AsyncSelect } from '@/components/common/AsyncSelect';
import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

const budgetStatusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'approved', label: 'Approved' },
    { value: 'locked', label: 'Locked' },
    { value: 'cancelled', label: 'Cancelled' },
];

const budgetTypeOptions = [
    { value: 'operational', label: 'Operational' },
    { value: 'capital', label: 'Capital' },
    { value: 'project', label: 'Project' },
    { value: 'revenue', label: 'Revenue' },
];

export function createBudgetFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            'Search budgets...',
        ),
        createSelectFilterField(
            'status',
            { key: 'column.status', fallback: 'Status' },
            budgetStatusOptions,
            'All statuses',
        ),
        createSelectFilterField(
            'budget_type',
            { key: 'column.budget_type', fallback: 'Budget Type' },
            budgetTypeOptions,
            'All types',
        ),
        {
            name: 'fiscal_year_id',
            label: { key: 'column.fiscal_year', fallback: 'Fiscal Year' },
            component: (
                <AsyncSelect
                    url="/api/fiscal-years"
                    placeholder="All fiscal years"
                    preferredMetaKey="preferred_fiscal_year_id"
                />
            ),
        },
    ];
}
