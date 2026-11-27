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

const statusOptions = [
    { value: 'draft', label: { key: 'status.draft', fallback: 'Draft' } },
    { value: 'closed', label: { key: 'status.closed', fallback: 'Closed' } },
];

const closingTypeOptions = [
    {
        value: 'monthly',
        label: {
            key: 'filter.period_closing_type_monthly',
            fallback: 'Monthly',
        },
    },
    {
        value: 'annual',
        label: { key: 'filter.period_closing_type_annual', fallback: 'Annual' },
    },
];

function FiscalYearFilterSelect({
    placeholder,
}: Readonly<{ placeholder: TranslatableLabel }>) {
    const translatedPlaceholder = useTranslatableText(placeholder);
    return (
        <AsyncSelect
            url="/api/fiscal-years"
            placeholder={translatedPlaceholder}
        />
    );
}

export function createPeriodClosingFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.period_closings.search_placeholder',
                fallback: 'Search...',
            },
        ),
        createSelectFilterField(
            'status',
            { key: 'column.status', fallback: 'Status' },
            statusOptions,
            { key: 'filter.period_select_status', fallback: 'Select Status' },
        ),
        createSelectFilterField(
            'closing_type',
            { key: 'column.closing_type', fallback: 'Closing Type' },
            closingTypeOptions,
            { key: 'filter.period_select_type', fallback: 'Select Type' },
        ),
        {
            name: 'fiscal_year_id',
            label: { key: 'column.fiscal_year', fallback: 'Fiscal Year' },
            component: (
                <FiscalYearFilterSelect
                    placeholder={{
                        key: 'filter.period_select_fiscal_year',
                        fallback: 'Select Fiscal Year',
                    }}
                />
            ),
        },
    ];
}
