import {
    createDateRangeFilterFields,
    createSelectFilterField,
    TranslatedAsyncSelect,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';

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

export function createGeneralLedgerFilterFields(): FieldDescriptor[] {
    return [
        {
            name: 'account_id',
            label: {
                key: 'filter.gl_account_required',
                fallback: 'Account (Required)',
            },
            component: (
                <TranslatedAsyncSelect
                    url="/api/accounts?is_active=1&has_children=0"
                    placeholder={{
                        key: 'filter.gl_select_account',
                        fallback: 'Select Account',
                    }}
                />
            ),
        },
        {
            name: 'fiscal_year_id',
            label: { key: 'filter.gl_fiscal_year', fallback: 'Fiscal Year' },
            component: (
                <TranslatedAsyncSelect
                    url="/api/fiscal-years"
                    placeholder={{
                        key: 'filter.gl_select_fiscal_year',
                        fallback: 'Select Fiscal Year',
                    }}
                    preferredMetaKey="preferred_fiscal_year_id"
                />
            ),
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
