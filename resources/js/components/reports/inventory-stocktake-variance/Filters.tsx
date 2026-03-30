import {
    createAsyncSelectFilterField,
    createDateRangeFilterFields,
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
        createAsyncSelectFilterField(
            'product_id',
            'Product',
            '/api/products',
            'All products',
        ),
        createAsyncSelectFilterField(
            'warehouse_id',
            'Warehouse',
            '/api/warehouses',
            'All warehouses',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'All branches',
        ),
        createAsyncSelectFilterField(
            'category_id',
            'Category',
            '/api/product-categories',
            'All categories',
        ),
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
