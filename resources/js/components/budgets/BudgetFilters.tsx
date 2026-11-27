import { AsyncSelect } from '@/components/common/AsyncSelect';
import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';
import {
    type TranslatableLabel,
    useTranslatableText,
} from '@/components/common/TranslatedText';

function FiscalYearFilterSelect({
    placeholder,
}: Readonly<{ placeholder: TranslatableLabel }>) {
    const translatedPlaceholder = useTranslatableText(placeholder);
    return (
        <AsyncSelect
            url="/api/fiscal-years"
            placeholder={translatedPlaceholder}
            preferredMetaKey="preferred_fiscal_year_id"
        />
    );
}

const budgetStatusOptions = [
    { value: 'draft', label: { key: 'status.draft', fallback: 'Draft' } },
    {
        value: 'approved',
        label: { key: 'status.approved', fallback: 'Approved' },
    },
    { value: 'locked', label: { key: 'status.locked', fallback: 'Locked' } },
    {
        value: 'cancelled',
        label: { key: 'status.cancelled', fallback: 'Cancelled' },
    },
];

const budgetTypeOptions = [
    {
        value: 'operational',
        label: {
            key: 'filter.budget_type_operational',
            fallback: 'Operational',
        },
    },
    {
        value: 'capital',
        label: { key: 'filter.budget_type_capital', fallback: 'Capital' },
    },
    {
        value: 'project',
        label: { key: 'filter.budget_type_project', fallback: 'Project' },
    },
    {
        value: 'revenue',
        label: { key: 'filter.budget_type_revenue', fallback: 'Revenue' },
    },
];

export function createBudgetFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.budgets.search_placeholder',
                fallback: 'Search budgets...',
            },
        ),
        createSelectFilterField(
            'status',
            { key: 'column.status', fallback: 'Status' },
            budgetStatusOptions,
            { key: 'filter.all_statuses', fallback: 'All statuses' },
        ),
        createSelectFilterField(
            'budget_type',
            { key: 'column.budget_type', fallback: 'Budget Type' },
            budgetTypeOptions,
            { key: 'filter.all_types', fallback: 'All types' },
        ),
        {
            name: 'fiscal_year_id',
            label: { key: 'column.fiscal_year', fallback: 'Fiscal Year' },
            component: (
                <FiscalYearFilterSelect
                    placeholder={{
                        key: 'filter.all_fiscal_years',
                        fallback: 'All fiscal years',
                    }}
                />
            ),
        },
    ];
}
