import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createVarianceFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search code, name, notes...'),
        createAsyncSelectFilterField(
            'asset_stocktake_id',
            'Stocktake',
            '/api/asset-stocktakes',
            'Select a stocktake',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'Select a branch',
        ),
        createSelectFilterField(
            'result',
            'Result',
            [
                { value: 'damaged', label: 'Damaged' },
                { value: 'missing', label: 'Missing' },
                { value: 'moved', label: 'Moved' },
            ],
            'Select result'
        ),
    ];
}
