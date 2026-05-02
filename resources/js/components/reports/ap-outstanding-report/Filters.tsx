import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';
import { FilterDatePicker } from '@/components/common/FilterDatePicker';

export function createApOutstandingReportFilterFields(): FieldDescriptor[] {
    const statusOptions: Array<{value: string, label: string}> = [
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
            name: 'due_date_from',
            label: 'Due Date From',
            component: <FilterDatePicker placeholder="From" />,
        },
        {
            name: 'due_date_to',
            label: 'Due Date To',
            component: <FilterDatePicker placeholder="To" />,
        },
    ];
}