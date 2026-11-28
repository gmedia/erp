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

function AccountFilterSelect({
    placeholder,
}: Readonly<{ placeholder: TranslatableLabel }>) {
    const translatedPlaceholder = useTranslatableText(placeholder);
    return (
        <AsyncSelect
            url="/api/accounts?is_active=1&has_children=0"
            placeholder={translatedPlaceholder}
        />
    );
}

const statusOptions = [
    {
        value: 'in_progress',
        label: {
            key: 'filter.reconciliation_status_in_progress',
            fallback: 'In Progress',
        },
    },
    {
        value: 'completed',
        label: {
            key: 'filter.reconciliation_status_completed',
            fallback: 'Completed',
        },
    },
];

export function createBankReconciliationFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.bank_reconciliations.search_placeholder',
                fallback: 'Search...',
            },
        ),
        createSelectFilterField(
            'status',
            { key: 'column.status', fallback: 'Status' },
            statusOptions,
            { key: 'filter.select_status', fallback: 'Select Status' },
        ),
        {
            name: 'account_id',
            label: { key: 'column.account', fallback: 'Account' },
            component: (
                <AccountFilterSelect
                    placeholder={{
                        key: 'filter.select_account',
                        fallback: 'Select Account',
                    }}
                />
            ),
        },
    ];
}
