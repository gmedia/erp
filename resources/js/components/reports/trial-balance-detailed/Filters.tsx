import {
    createSelectFilterField,
    TranslatedAsyncSelect,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';

const monthNames = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

const monthOptions: SelectOption[] = monthNames.map((fallback, index) => ({
    value: String(index + 1),
    label: { key: `filter.month_${index + 1}`, fallback },
}));

export function createTrialBalanceDetailedFilterFields(): FieldDescriptor[] {
    return [
        {
            name: 'fiscal_year_id',
            label: { key: 'filter.gl_fiscal_year', fallback: 'Fiscal Year' },
            component: (
                <TranslatedAsyncSelect
                    url="/api/fiscal-years"
                    placeholder={{
                        key: 'filter.select_fiscal_year',
                        fallback: 'Select fiscal year',
                    }}
                    preferredMetaKey="preferred_fiscal_year_id"
                />
            ),
        },
        createSelectFilterField(
            'period_month',
            { key: 'filter_field.month', fallback: 'Month' },
            monthOptions,
            { key: 'filter.all_months', fallback: 'All months' },
        ),
    ];
}
