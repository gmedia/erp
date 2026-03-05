'use client';

import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createStockAdjustmentFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search stock adjustments...'),
        createAsyncSelectFilterField(
            'warehouse_id',
            'Warehouse',
            '/api/warehouses',
            'Select warehouse',
        ),
        createSelectFilterField(
            'status',
            'Status',
            [
                { label: 'Draft', value: 'draft' },
                { label: 'Pending Approval', value: 'pending_approval' },
                { label: 'Approved', value: 'approved' },
                { label: 'Cancelled', value: 'cancelled' },
            ],
            'Select status',
        ),
        createSelectFilterField(
            'adjustment_type',
            'Adjustment Type',
            [
                { label: 'Damage', value: 'damage' },
                { label: 'Expired', value: 'expired' },
                { label: 'Shrinkage', value: 'shrinkage' },
                { label: 'Correction', value: 'correction' },
                { label: 'Stocktake Result', value: 'stocktake_result' },
                { label: 'Initial Stock', value: 'initial_stock' },
                { label: 'Other', value: 'other' },
            ],
            'Select type',
        ),
    ];
}
