import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';
import { FilterDatePicker } from '@/components/common/FilterDatePicker';

export function createApAgingReportFilterFields(): FieldDescriptor[] {
    const statusOptions: SelectOption[] = [
        { value: 'confirmed', label: 'Confirmed' },
        { value: 'partially_paid', label: 'Partially Paid' },
        { value: 'overdue', label: 'Overdue' },
    ];

    return [
        createTextFilterField(
            'search',
            'Search',
            'Search bill number, supplier invoice, supplier, branch, PO number, GR number, or notes...',
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
        createSelectFilterField('status', 'Status', statusOptions, 'Select status'),
        {
            name: 'as_of_date',
            label: 'As of Date',
            component: <FilterDatePicker placeholder="Select aging calculation date" />,
        },
    ];
}