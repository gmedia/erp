import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';
import { useTranslatableText } from '@/components/common/TranslatedText';

function TranslatedAsOfDatePicker() {
    const placeholder = useTranslatableText({
        key: 'filter.select_aging_date',
        fallback: 'Select aging calculation date',
    });
    return <FilterDatePicker placeholder={placeholder} />;
}

export function createApAgingReportFilterFields(): FieldDescriptor[] {
    const statusOptions: SelectOption[] = [
        {
            value: 'confirmed',
            label: { key: 'status.confirmed', fallback: 'Confirmed' },
        },
        {
            value: 'partially_paid',
            label: { key: 'status.partially_paid', fallback: 'Partially Paid' },
        },
        {
            value: 'overdue',
            label: { key: 'status.overdue', fallback: 'Overdue' },
        },
    ];

    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.ap_aging.search_placeholder',
                fallback:
                    'Search bill number, supplier invoice, supplier, branch, PO number, GR number, or notes...',
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
            'status',
            { key: 'filter_field.status', fallback: 'Status' },
            statusOptions,
            { key: 'filter.select_status', fallback: 'Select status' },
        ),
        {
            name: 'as_of_date',
            label: { key: 'filter_field.as_of_date', fallback: 'As of Date' },
            component: <TranslatedAsOfDatePicker />,
        },
    ];
}
