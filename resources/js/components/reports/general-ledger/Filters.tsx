import {
    createDateRangeFilterFields,
    createSelectFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';
import { AsyncSelect } from '@/components/common/AsyncSelect';

const journalTypeOptions = [
    { value: 'general', label: 'General' },
    { value: 'sales', label: 'Sales' },
    { value: 'purchase', label: 'Purchase' },
    { value: 'cash_receipt', label: 'Cash Receipt' },
    { value: 'cash_disbursement', label: 'Cash Disbursement' },
];

export function createGeneralLedgerFilterFields(): FieldDescriptor[] {
    return [
        {
            name: 'account_id',
            label: 'Account (Required)',
            component: <AsyncSelect url="/api/accounts?is_active=1&has_children=0" placeholder="Select Account" />,
        },
        {
            name: 'fiscal_year_id',
            label: 'Fiscal Year',
            component: <AsyncSelect url="/api/fiscal-years" placeholder="Select Fiscal Year" />,
        },
        ...createDateRangeFilterFields(),
        createSelectFilterField('journal_type', 'Journal Type', journalTypeOptions, 'Select Type'),
    ];
}
