import {
    createAsyncSelectFilterField,
    createDateRangeFilterFields,
    createProductWarehouseBranchCategoryFilterFields,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createInventoryStocktakeVarianceFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search stocktake, product, category, warehouse...',
        ),
        createAsyncSelectFilterField(
            'inventory_stocktake_id',
            'Stocktake',
            '/api/inventory-stocktakes',
            'All stocktakes',
        ),
        ...createProductWarehouseBranchCategoryFilterFields(),
        createSelectFilterField(
            'result',
            'Result',
            [
                { value: 'surplus', label: 'Surplus' },
                { value: 'deficit', label: 'Deficit' },
            ],
            'All results',
        ),
        ...createDateRangeFilterFields(),
    ];
}
