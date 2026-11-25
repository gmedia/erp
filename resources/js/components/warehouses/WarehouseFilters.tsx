import {
    createAsyncSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createWarehouseFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            'Search warehouses...',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            { key: 'column.branch', fallback: 'Branch' },
            '/api/branches',
            'All Branches',
        ),
    ];
}
