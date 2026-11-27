import { AsyncSelect } from '@/components/common/AsyncSelect';
import {
    createSelectFilterField,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';
import { useTranslatableText } from '@/components/common/TranslatedText';

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

function TranslatedFiscalYearSelect() {
    const placeholder = useTranslatableText({
        key: 'filter.select_fiscal_year',
        fallback: 'Select fiscal year',
    });
    return (
        <AsyncSelect
            url="/api/fiscal-years"
            placeholder={placeholder}
            preferredMetaKey="preferred_fiscal_year_id"
        />
    );
}

export function createTrialBalanceDetailedFilterFields(): FieldDescriptor[] {
    return [
        {
            name: 'fiscal_year_id',
            label: { key: 'filter.gl_fiscal_year', fallback: 'Fiscal Year' },
            component: <TranslatedFiscalYearSelect />,
        },
        createSelectFilterField(
            'period_month',
            { key: 'filter_field.month', fallback: 'Month' },
            monthOptions,
            { key: 'filter.all_months', fallback: 'All months' },
        ),
    ];
}
