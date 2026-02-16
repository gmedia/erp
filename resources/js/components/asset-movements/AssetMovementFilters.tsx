import { FieldDescriptor, createAsyncSelectFilterField, createSelectFilterField, createTextFilterField } from '@/components/common/filters';

export const createAssetMovementFilterFields = (): FieldDescriptor[] => [
    createTextFilterField(
        'search',
        'Search',
        'Search movements...'
    ),
    createAsyncSelectFilterField(
        'asset_id',
        'Asset',
        '/api/assets',
        'Filter by asset'
    ),
    createSelectFilterField(
        'movement_type',
        'Type',
        [
            { value: 'transfer', label: 'Transfer' },
            { value: 'assign', label: 'Assign' },
            { value: 'return', label: 'Return' },
            { value: 'dispose', label: 'Dispose' },
            { value: 'adjustment', label: 'Adjustment' },
        ],
        'Filter by type'
    ),
];
