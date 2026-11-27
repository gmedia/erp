'use client';

import {
    approvalWorkflowStatusOptions,
    createSelectFilterField,
    createTextFilterField,
    createWarehouseStatusFilterFields,
    stockAdjustmentTypeOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createStockAdjustmentFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.stock_adjustments.search_placeholder',
                fallback: 'Search stock adjustments...',
            },
        ),
        ...createWarehouseStatusFilterFields(
            approvalWorkflowStatusOptions,
            {
                key: 'filter_field.select_warehouse',
                fallback: 'Select warehouse',
            },
            { key: 'filter_field.select_status', fallback: 'Select status' },
        ),
        createSelectFilterField(
            'adjustment_type',
            { key: 'column.adjustment_type', fallback: 'Adjustment Type' },
            stockAdjustmentTypeOptions,
            { key: 'filter.select_type', fallback: 'Select type' },
        ),
    ];
}
