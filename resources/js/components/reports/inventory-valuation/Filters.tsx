import {
    createProductWarehouseBranchCategoryFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createInventoryValuationFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search product, category, warehouse, branch...',
        ),
        ...createProductWarehouseBranchCategoryFilterFields(),
    ];
}
