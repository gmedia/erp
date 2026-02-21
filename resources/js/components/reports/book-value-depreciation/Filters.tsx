import {
    createAsyncSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createBookValueReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search code, name...'),
        createAsyncSelectFilterField(
            'asset_category_id',
            'Category',
            '/api/asset-categories',
            'Select a category',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'Select a branch',
        ),
    ];
}
