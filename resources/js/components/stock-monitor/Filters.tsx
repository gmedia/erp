import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';

type StockMonitorFilterOptions = {
    products: SelectOption[];
    warehouses: SelectOption[];
    branches: SelectOption[];
    categories: SelectOption[];
};

export function createStockMonitorFilterFields(
    options: StockMonitorFilterOptions,
): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search product, category, warehouse, branch...',
        ),
        createSelectFilterField(
            'product_id',
            'Product',
            options.products,
            'All products',
        ),
        createSelectFilterField(
            'warehouse_id',
            'Warehouse',
            options.warehouses,
            'All warehouses',
        ),
        createSelectFilterField(
            'branch_id',
            'Branch',
            options.branches,
            'All branches',
        ),
        createSelectFilterField(
            'category_id',
            'Category',
            options.categories,
            'All categories',
        ),
        createTextFilterField(
            'low_stock_threshold',
            'Low Stock Threshold',
            'Example: 10',
        ),
    ];
}
