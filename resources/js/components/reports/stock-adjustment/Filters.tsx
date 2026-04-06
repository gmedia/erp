import {
    createDateRangeFilterFields,
    createSelectFilterField,
    createTextFilterField,
    createWarehouseBranchFilterFields,
    stockAdjustmentStatusOptions,
    stockAdjustmentTypeOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createStockAdjustmentReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search number, warehouse, branch, type, status...',
        ),
        ...createWarehouseBranchFilterFields(),
        createSelectFilterField(
            'adjustment_type',
            'Adjustment Type',
            stockAdjustmentTypeOptions,
            'All types',
        ),
        createSelectFilterField(
            'status',
            'Status',
            stockAdjustmentStatusOptions,
            'All statuses',
        ),
        ...createDateRangeFilterFields(),
    ];
}
