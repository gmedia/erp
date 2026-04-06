import {
    createDateRangeFilterFields,
    createProductWarehouseBranchCategoryFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createStockMovementReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search product, category, warehouse, branch...',
        ),
        ...createProductWarehouseBranchCategoryFilterFields(),
        ...createDateRangeFilterFields(),
    ];
}
