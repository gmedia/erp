import { AsyncSelect } from '@/components/common/AsyncSelect';
import {
    createDateRangeFilterFields,
    createSelectFilterField,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';
import { useTranslatableText } from '@/components/common/TranslatedText';

const journalTypeOptions: SelectOption[] = [
    {
        value: 'general',
        label: { key: 'filter.gl_journal_general', fallback: 'General' },
    },
    {
        value: 'sales',
        label: { key: 'filter.gl_journal_sales', fallback: 'Sales' },
    },
    {
        value: 'purchase',
        label: { key: 'filter.gl_journal_purchase', fallback: 'Purchase' },
    },
    {
        value: 'cash_receipt',
        label: {
            key: 'filter.gl_journal_cash_receipt',
            fallback: 'Cash Receipt',
        },
    },
    {
        value: 'cash_disbursement',
        label: {
            key: 'filter.gl_journal_cash_disbursement',
            fallback: 'Cash Disbursement',
        },
    },
];

function TranslatedAccountSelect() {
    const placeholder = useTranslatableText({
        key: 'filter.gl_select_account',
        fallback: 'Select Account',
    });
    return (
        <AsyncSelect
            url="/api/accounts?is_active=1&has_children=0"
            placeholder={placeholder}
        />
    );
}

function TranslatedFiscalYearSelect() {
    const placeholder = useTranslatableText({
        key: 'filter.gl_select_fiscal_year',
        fallback: 'Select Fiscal Year',
    });
    return (
        <AsyncSelect
            url="/api/fiscal-years"
            placeholder={placeholder}
            preferredMetaKey="preferred_fiscal_year_id"
        />
    );
}

export function createGeneralLedgerFilterFields(): FieldDescriptor[] {
    return [
        {
            name: 'account_id',
            label: {
                key: 'filter.gl_account_required',
                fallback: 'Account (Required)',
            },
            component: <TranslatedAccountSelect />,
        },
        {
            name: 'fiscal_year_id',
            label: { key: 'filter.gl_fiscal_year', fallback: 'Fiscal Year' },
            component: <TranslatedFiscalYearSelect />,
        },
        ...createDateRangeFilterFields(),
        createSelectFilterField(
            'journal_type',
            { key: 'filter.gl_journal_type', fallback: 'Journal Type' },
            journalTypeOptions,
            { key: 'filter.gl_select_journal_type', fallback: 'Select Type' },
        ),
    ];
}
