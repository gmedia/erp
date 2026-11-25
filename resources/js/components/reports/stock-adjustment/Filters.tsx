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
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.stock_adjustment.search_placeholder',
                fallback: 'Search number, warehouse, branch, type, status...',
            },
        ),
        ...createWarehouseBranchFilterFields(),
        createSelectFilterField(
            'adjustment_type',
            { key: 'column.adjustment_type', fallback: 'Adjustment Type' },
            stockAdjustmentTypeOptions,
            'All types',
        ),
        createSelectFilterField(
            'status',
            { key: 'column.status', fallback: 'Status' },
            stockAdjustmentStatusOptions,
            'All statuses',
        ),
        ...createDateRangeFilterFields(),
    ];
}
