import { AsyncSelect } from '@/components/common/AsyncSelect';
import {
    createSelectFilterField,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';
import { useTranslatableText } from '@/components/common/TranslatedText';

const varianceStatusOptions: SelectOption[] = [
    {
        value: 'within_budget',
        label: { key: 'filter.within_budget', fallback: 'Within Budget' },
    },
    {
        value: 'warning',
        label: { key: 'filter.warning', fallback: 'Warning' },
    },
    {
        value: 'over_budget',
        label: { key: 'filter.over_budget', fallback: 'Over Budget' },
    },
];

const accountTypeOptions: SelectOption[] = [
    {
        value: 'asset',
        label: { key: 'filter.account_type_asset', fallback: 'Asset' },
    },
    {
        value: 'liability',
        label: { key: 'filter.account_type_liability', fallback: 'Liability' },
    },
    {
        value: 'equity',
        label: { key: 'filter.account_type_equity', fallback: 'Equity' },
    },
    {
        value: 'revenue',
        label: { key: 'filter.account_type_revenue', fallback: 'Revenue' },
    },
    {
        value: 'expense',
        label: { key: 'filter.account_type_expense', fallback: 'Expense' },
    },
];

function TranslatedBudgetSelect() {
    const placeholder = useTranslatableText({
        key: 'filter.select_budget',
        fallback: 'Select budget',
    });
    return <AsyncSelect url="/api/budgets" placeholder={placeholder} />;
}

export function createBudgetVarianceFilterFields(): FieldDescriptor[] {
    return [
        {
            name: 'budget_id',
            label: { key: 'filter_field.budget', fallback: 'Budget' },
            component: <TranslatedBudgetSelect />,
        },
        createSelectFilterField(
            'status',
            { key: 'filter_field.status', fallback: 'Status' },
            varianceStatusOptions,
            { key: 'filter.all_budget_statuses', fallback: 'All statuses' },
        ),
        createSelectFilterField(
            'account_type',
            { key: 'filter_field.account_type', fallback: 'Account Type' },
            accountTypeOptions,
            { key: 'filter.all_account_types', fallback: 'All types' },
        ),
    ];
}
