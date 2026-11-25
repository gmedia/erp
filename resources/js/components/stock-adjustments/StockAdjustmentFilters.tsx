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
            'Search',
            'Search stock adjustments...',
        ),
        ...createWarehouseStatusFilterFields(
            approvalWorkflowStatusOptions,
            'Select warehouse',
            'Select status',
        ),
        createSelectFilterField(
            'adjustment_type',
            { key: 'column.adjustment_type', fallback: 'Adjustment Type' },
            stockAdjustmentTypeOptions,
            'Select type',
        ),
    ];
}
