import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createStockAdjustmentReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search number, warehouse, branch, type, status...',
        ),
        createAsyncSelectFilterField(
            'warehouse_id',
            'Warehouse',
            '/api/warehouses',
            'All warehouses',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'All branches',
        ),
        createSelectFilterField(
            'adjustment_type',
            'Adjustment Type',
            [
                { value: 'damage', label: 'Damage' },
                { value: 'expired', label: 'Expired' },
                { value: 'shrinkage', label: 'Shrinkage' },
                { value: 'correction', label: 'Correction' },
                { value: 'stocktake_result', label: 'Stocktake Result' },
                { value: 'initial_stock', label: 'Initial Stock' },
                { value: 'other', label: 'Other' },
            ],
            'All types',
        ),
        createSelectFilterField(
            'status',
            'Status',
            [
                { value: 'draft', label: 'Draft' },
                { value: 'pending_approval', label: 'Pending Approval' },
                { value: 'approved', label: 'Approved' },
                { value: 'cancelled', label: 'Cancelled' },
            ],
            'All statuses',
        ),
        {
            name: 'start_date',
            label: 'Start Date',
            component: <FilterDatePicker placeholder="Start Date" />,
        },
        {
            name: 'end_date',
            label: 'End Date',
            component: <FilterDatePicker placeholder="End Date" />,
        },
    ];
}
