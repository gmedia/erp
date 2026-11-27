import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';
import { useTranslatableText } from '@/components/common/TranslatedText';

function TranslatedDatePicker({
    placeholder,
}: Readonly<{ placeholder: { key: string; fallback: string } }>) {
    const translated = useTranslatableText(placeholder);
    return <FilterDatePicker placeholder={translated} />;
}

export function createApPaymentHistoryReportFilterFields(): FieldDescriptor[] {
    const paymentMethodOptions: SelectOption[] = [
        {
            value: 'bank_transfer',
            label: { key: 'filter.bank_transfer', fallback: 'Bank Transfer' },
        },
        { value: 'cash', label: { key: 'filter.cash', fallback: 'Cash' } },
        { value: 'check', label: { key: 'filter.check', fallback: 'Check' } },
        { value: 'giro', label: { key: 'filter.giro', fallback: 'Giro' } },
        { value: 'other', label: { key: 'filter.other', fallback: 'Other' } },
    ];

    const statusOptions: SelectOption[] = [
        {
            value: 'confirmed',
            label: { key: 'status.confirmed', fallback: 'Confirmed' },
        },
        {
            value: 'reconciled',
            label: { key: 'status.reconciled', fallback: 'Reconciled' },
        },
    ];

    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.ap_payment_history.search_placeholder',
                fallback:
                    'Search payment number, supplier, branch, bank account, reference, or notes...',
            },
        ),
        createAsyncSelectFilterField(
            'supplier_id',
            { key: 'column.supplier', fallback: 'Supplier' },
            '/api/suppliers/select-options',
            { key: 'filter.select_supplier', fallback: 'Select a supplier' },
        ),
        createAsyncSelectFilterField(
            'branch_id',
            { key: 'column.branch', fallback: 'Branch' },
            '/api/branches/select-options',
            { key: 'filter.select_branch', fallback: 'Select a branch' },
        ),
        createSelectFilterField(
            'payment_method',
            { key: 'column.payment_method', fallback: 'Payment Method' },
            paymentMethodOptions,
            { key: 'filter.select_method', fallback: 'Select method' },
        ),
        createSelectFilterField(
            'status',
            { key: 'filter_field.status', fallback: 'Status' },
            statusOptions,
            { key: 'filter.select_status', fallback: 'Select status' },
        ),
        {
            name: 'payment_date_from',
            label: {
                key: 'filter_field.payment_date_from',
                fallback: 'Payment Date From',
            },
            component: (
                <TranslatedDatePicker
                    placeholder={{ key: 'filter.from', fallback: 'From' }}
                />
            ),
        },
        {
            name: 'payment_date_to',
            label: {
                key: 'filter_field.payment_date_to',
                fallback: 'Payment Date To',
            },
            component: (
                <TranslatedDatePicker
                    placeholder={{ key: 'filter.to', fallback: 'To' }}
                />
            ),
        },
    ];
}
