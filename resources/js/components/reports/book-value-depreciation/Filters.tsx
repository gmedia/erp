import {
    createAssetCategoryBranchFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createBookValueReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search code, name...'),
        ...createAssetCategoryBranchFilterFields(),
    ];
}
