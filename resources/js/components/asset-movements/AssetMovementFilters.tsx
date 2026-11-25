import {
    FieldDescriptor,
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
} from '@/components/common/filters';

export const createAssetMovementFilterFields = (): FieldDescriptor[] => [
    createTextFilterField(
        'search',
        { key: 'column.search', fallback: 'Search' },
        {
            key: 'page.asset_movements.search_placeholder',
            fallback: 'Search movements...',
        },
    ),
    createAsyncSelectFilterField(
        'asset_id',
        { key: 'column.asset', fallback: 'Asset' },
        '/api/assets',
        'Filter by asset',
    ),
    createSelectFilterField(
        'movement_type',
        { key: 'column.type', fallback: 'Type' },
        [
            {
                value: 'transfer',
                label: { key: 'movement_type.transfer', fallback: 'Transfer' },
            },
            {
                value: 'assign',
                label: { key: 'movement_type.assign', fallback: 'Assign' },
            },
            {
                value: 'return',
                label: { key: 'movement_type.return', fallback: 'Return' },
            },
            {
                value: 'dispose',
                label: { key: 'movement_type.dispose', fallback: 'Dispose' },
            },
            {
                value: 'adjustment',
                label: {
                    key: 'movement_type.adjustment',
                    fallback: 'Adjustment',
                },
            },
        ],
        'Filter by type',
    ),
];
