import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';

export function createApPaymentHistoryReportFilterFields(): FieldDescriptor[] {
    const paymentMethodOptions: SelectOption[] = [
        { value: 'bank_transfer', label: 'Bank Transfer' },
        { value: 'cash', label: 'Cash' },
        { value: 'check', label: 'Check' },
        { value: 'giro', label: 'Giro' },
        { value: 'other', label: 'Other' },
    ];

    const statusOptions: SelectOption[] = [
        { value: 'confirmed', label: 'Confirmed' },
        { value: 'reconciled', label: 'Reconciled' },
    ];

    return [
        createTextFilterField(
            'search',
            'Search',
            'Search payment number, supplier, branch, bank account, reference, or notes...',
        ),
        createAsyncSelectFilterField(
            'supplier_id',
            'Supplier',
            'Select supplier',
            '/api/suppliers/select-options',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            'Select branch',
            '/api/branches/select-options',
        ),
        createSelectFilterField(
            'payment_method',
            'Payment Method',
            paymentMethodOptions,
            'Select method',
        ),
        createSelectFilterField(
            'status',
            'Status',
            statusOptions,
            'Select status',
        ),
        {
            name: 'payment_date_from',
            label: 'Payment Date From',
            component: <FilterDatePicker placeholder="From" />,
        },
        {
            name: 'payment_date_to',
            label: 'Payment Date To',
            component: <FilterDatePicker placeholder="To" />,
        },
    ];
}
