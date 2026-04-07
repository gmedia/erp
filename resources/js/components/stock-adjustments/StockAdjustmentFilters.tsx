'use client';

import {
    createSelectFilterField,
    createTextFilterField,
    createWarehouseStatusFilterFields,
    approvalWorkflowStatusOptions,
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
            'Adjustment Type',
            stockAdjustmentTypeOptions,
            'Select type',
        ),
    ];
}
