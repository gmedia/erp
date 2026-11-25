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
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.stock_monitor.search_placeholder',
                fallback: 'Search product, category, warehouse, branch...',
            },
        ),
        createSelectFilterField(
            'product_id',
            { key: 'column.product', fallback: 'Product' },
            options.products,
            'All products',
        ),
        createSelectFilterField(
            'warehouse_id',
            { key: 'column.warehouse', fallback: 'Warehouse' },
            options.warehouses,
            'All warehouses',
        ),
        createSelectFilterField(
            'branch_id',
            { key: 'column.branch', fallback: 'Branch' },
            options.branches,
            'All branches',
        ),
        createSelectFilterField(
            'category_id',
            { key: 'column.category', fallback: 'Category' },
            options.categories,
            'All categories',
        ),
        createTextFilterField(
            'low_stock_threshold',
            {
                key: 'column.low_stock_threshold',
                fallback: 'Low Stock Threshold',
            },
            'Example: 10',
        ),
    ];
}
